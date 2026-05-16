<?php

namespace App\Services;

use App\Models\Companions\UserCompanion;
use App\Models\Creatures\Skill;
use App\Models\Skills\UserSkill;
use App\Models\User;
use App\Models\World\AdventureNode;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildService
{
    public function preparationData(User $user, AdventureNode $node): array
    {
        $user->loadMissing('unlockedSkills.learningCategory');

        $companions = $user->userCompanions()
            ->with([
                'companion.uniquePassiveSkill.learningCategory',
                'companion.signatureSkill.learningCategory',
                'unlockedSkills.learningCategory',
            ])
            ->orderByDesc('is_favorite')
            ->orderBy('id')
            ->get();

        $selectedCompanions = $companions
            ->filter(fn (UserCompanion $companion): bool => in_array((int) $companion->id, $this->defaultSelectedCompanionIds($user, $companions), true))
            ->values();

        $hero = $this->heroBuildCard($user);
        $companionCards = $companions->map(fn (UserCompanion $companion): array => $this->companionBuildCard($companion))->values();
        $synergyState = $this->synergyState($hero, $selectedCompanions->map(fn (UserCompanion $companion): array => $this->companionCombatBuild($companion)));

        return [
            'node' => $node,
            'heroBuild' => $hero,
            'companions' => $companionCards->all(),
            'selectedCompanionIds' => $selectedCompanions->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'synergies' => $synergyState['synergies'],
            'estimatedPower' => $synergyState['estimated_power'],
            'strengths' => $synergyState['strengths'],
            'weaknesses' => $synergyState['weaknesses'],
            'loadoutLimits' => config('builds.loadout_limits'),
        ];
    }

    public function savePreparation(User $user, array $payload): array
    {
        $heroClass = (string) ($payload['hero_class'] ?? $user->hero_class ?? 'strategist');
        $heroSpec = (string) ($payload['hero_specialization'] ?? $user->specialization_slug ?? 'tactician');
        $heroConfig = $this->specializationConfig($heroClass, $heroSpec);

        if ($heroConfig === null) {
            throw new InvalidArgumentException('La spécialisation du héros est invalide.');
        }

        $selectedCompanionIds = collect($payload['user_companion_ids'] ?? [])
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->take((int) config('combat.party.max_companions', 2))
            ->values();

        $companions = $user->userCompanions()
            ->with([
                'companion.uniquePassiveSkill.learningCategory',
                'companion.signatureSkill.learningCategory',
                'unlockedSkills.learningCategory',
            ])
            ->whereIn('id', $selectedCompanionIds)
            ->get()
            ->keyBy('id');

        if ($selectedCompanionIds->isNotEmpty() && $companions->count() !== $selectedCompanionIds->count()) {
            throw new InvalidArgumentException('Un compagnon sélectionné est invalide.');
        }

        $user->hero_class = $heroClass;
        $user->combat_role = (string) ($heroConfig['role'] ?? config('builds.classes.'.$heroClass.'.default_role', 'hybrid'));
        $user->specialization_slug = $heroSpec;
        $user->combat_loadout = $this->normalizeLoadout(
            $this->heroAvailableSkills(
                $user,
                $user->combat_role,
                $heroSpec,
                (int) $user->level,
            ),
            [
                'active' => $payload['hero_active_skills'] ?? [],
                'passive' => $payload['hero_passive_skills'] ?? [],
                'ultimate' => $payload['hero_ultimate_skill'] ?? null,
            ],
        );
        $user->save();

        foreach ($companions as $companion) {
            $classKey = (string) ($companion->companion->combat_class ?: 'strategist');
            $specialization = (string) data_get($payload, 'companions.'.$companion->id.'.specialization_slug', $companion->specialization_slug ?: $companion->companion->specialization_slug ?: array_key_first(config('builds.classes.'.$classKey.'.specializations', [])));
            $specConfig = $this->specializationConfig($classKey, $specialization);

            if ($specConfig === null) {
                throw new InvalidArgumentException("La spécialisation de {$companion->companion->name} est invalide.");
            }

            $companion->build_role = (string) ($specConfig['role'] ?? config('builds.classes.'.$classKey.'.default_role', 'hybrid'));
            $companion->specialization_slug = $specialization;
            $companion->combat_position = max(2, min(3, (int) data_get($payload, 'companions.'.$companion->id.'.position', $companion->combat_position ?: 2)));
            $companion->combat_loadout = $this->normalizeLoadout(
                $this->companionAvailableSkills(
                    $companion,
                    $companion->build_role,
                    $specialization,
                    (int) $companion->level,
                ),
                [
                    'active' => data_get($payload, 'companions.'.$companion->id.'.active_skills', []),
                    'passive' => data_get($payload, 'companions.'.$companion->id.'.passive_skills', []),
                    'ultimate' => data_get($payload, 'companions.'.$companion->id.'.ultimate_skill'),
                ],
            );
            $companion->save();
        }

        return $selectedCompanionIds->all();
    }

    public function heroCombatBuild(User $user): array
    {
        $classKey = (string) ($user->hero_class ?: 'strategist');
        $specializationKey = (string) ($user->specialization_slug ?: 'tactician');
        $specialization = $this->specializationConfig($classKey, $specializationKey) ?? [];
        $roleKey = (string) ($user->combat_role ?: ($specialization['role'] ?? config('builds.classes.'.$classKey.'.default_role', 'hybrid')));
        $available = $this->heroAvailableSkills($user, $roleKey, $specializationKey, (int) $user->level);
        $loadout = $this->normalizeLoadout($available, $user->combat_loadout ?? []);
        $equipped = $this->equippedSkillsFromLoadout($available, $loadout);

        return $this->buildSnapshot(
            name: 'Héros',
            subjectType: 'hero',
            subjectReference: 0,
            classKey: $classKey,
            roleKey: $roleKey,
            specializationKey: $specializationKey,
            actorTags: $specialization['focus_tags'] ?? [],
            availableSkills: $available,
            equippedSkills: $equipped,
            loadout: $loadout,
            level: (int) $user->level,
            specialTitle: null,
        );
    }

    public function companionCombatBuild(UserCompanion $userCompanion): array
    {
        $classKey = (string) ($userCompanion->companion->combat_class ?: 'strategist');
        $specializationKey = (string) ($userCompanion->specialization_slug ?: $userCompanion->companion->specialization_slug ?: array_key_first(config('builds.classes.'.$classKey.'.specializations', [])));
        $specialization = $this->specializationConfig($classKey, $specializationKey) ?? [];
        $roleKey = (string) ($userCompanion->build_role ?: $userCompanion->companion->build_role ?: ($specialization['role'] ?? config('builds.classes.'.$classKey.'.default_role', 'hybrid')));
        $available = $this->companionAvailableSkills($userCompanion, $roleKey, $specializationKey, (int) $userCompanion->level);
        $loadout = $this->normalizeLoadout($available, $userCompanion->combat_loadout ?? []);
        $equipped = $this->equippedSkillsFromLoadout($available, $loadout);
        $actorTags = array_values(array_unique(array_merge(
            $specialization['focus_tags'] ?? [],
            $userCompanion->companion->build_tags ?? [],
        )));

        return $this->buildSnapshot(
            name: $userCompanion->companion->name,
            subjectType: 'companion',
            subjectReference: (int) $userCompanion->id,
            classKey: $classKey,
            roleKey: $roleKey,
            specializationKey: $specializationKey,
            actorTags: $actorTags,
            availableSkills: $available,
            equippedSkills: $equipped,
            loadout: $loadout,
            level: (int) $userCompanion->level,
            specialTitle: $userCompanion->companion->name,
        );
    }

    public function equippedHeroCombatSkills(User $user): Collection
    {
        return collect($this->heroCombatBuild($user)['active_skills']);
    }

    public function equippedHeroPassiveSkills(User $user): Collection
    {
        return collect($this->heroCombatBuild($user)['passive_skills']);
    }

    public function equippedCompanionCombatSkills(UserCompanion $userCompanion): Collection
    {
        return collect($this->companionCombatBuild($userCompanion)['active_skills']);
    }

    public function equippedCompanionPassiveSkills(UserCompanion $userCompanion): Collection
    {
        return collect($this->companionCombatBuild($userCompanion)['passive_skills']);
    }

    public function synergyState(array $heroBuild, Collection $companionBuilds): array
    {
        $actors = collect([$heroBuild])
            ->merge($companionBuilds)
            ->values();

        $actorTagsCount = [];
        $skillTagsCount = [];
        $roles = [];

        foreach ($actors as $actor) {
            $roles[] = $actor['role_key'];

            foreach ($actor['actor_tags'] as $tag) {
                $actorTagsCount[$tag] = ($actorTagsCount[$tag] ?? 0) + 1;
            }

            foreach (collect($actor['all_skills'])->pluck('tags')->flatten()->filter()->all() as $tag) {
                $skillTagsCount[$tag] = ($skillTagsCount[$tag] ?? 0) + 1;
            }
        }

        $effectsByActor = [];
        $synergies = [];

        foreach (config('builds.party_synergies', []) as $rule) {
            if (! $this->synergyRequirementsMet($rule['requirements'] ?? [], $roles, $actorTagsCount, $skillTagsCount)) {
                continue;
            }

            $synergies[] = [
                'key' => $rule['key'],
                'label' => $rule['label'],
                'description' => $rule['description'],
            ];

            foreach ($actors as $actor) {
                foreach ($rule['effects'] ?? [] as $effect) {
                    if (! $this->effectTargetsActor($effect, $actor)) {
                        continue;
                    }

                    $effectsByActor[$actor['subject_key']][] = $effect;
                }
            }
        }

        return [
            'synergies' => $synergies,
            'effects' => $effectsByActor,
            'estimated_power' => $this->estimatePower($actors, $synergies),
            'strengths' => $this->strengths($actors, $synergies),
            'weaknesses' => $this->weaknesses($actors),
        ];
    }

    public function roleLabel(?string $roleKey): string
    {
        return (string) data_get(config('builds.roles'), $roleKey.'.label', ucfirst((string) $roleKey));
    }

    public function classLabel(?string $classKey): string
    {
        return (string) data_get(config('builds.classes'), $classKey.'.label', ucfirst((string) $classKey));
    }

    public function specializationLabel(?string $classKey, ?string $specializationKey): string
    {
        return (string) data_get(config('builds.classes'), $classKey.'.specializations.'.$specializationKey.'.label', ucfirst(str_replace('-', ' ', (string) $specializationKey)));
    }

    public function tagLabel(string $tag): string
    {
        return (string) data_get(config('builds.tag_labels'), $tag, ucfirst($tag));
    }

    public function specializationOptions(string $classKey): array
    {
        return collect(config('builds.classes.'.$classKey.'.specializations', []))
            ->map(fn (array $config, string $key): array => [
                'key' => $key,
                'label' => $config['label'],
                'role' => $config['role'] ?? config('builds.classes.'.$classKey.'.default_role', 'hybrid'),
                'role_label' => $this->roleLabel($config['role'] ?? config('builds.classes.'.$classKey.'.default_role', 'hybrid')),
            ])
            ->values()
            ->all();
    }

    protected function heroBuildCard(User $user): array
    {
        $build = $this->heroCombatBuild($user);

        return [
            ...$build,
            'class_options' => collect(config('builds.classes', []))
                ->map(fn (array $config, string $key): array => [
                    'key' => $key,
                    'label' => $config['label'],
                ])
                ->values()
                ->all(),
            'specialization_options' => $this->specializationOptions($build['class_key']),
        ];
    }

    protected function companionBuildCard(UserCompanion $userCompanion): array
    {
        $build = $this->companionCombatBuild($userCompanion);

        return [
            ...$build,
            'id' => (int) $userCompanion->id,
            'sprite_url' => $userCompanion->companion->spriteUrl,
            'specialization_options' => $this->specializationOptions($build['class_key']),
            'position' => (int) ($userCompanion->combat_position ?: 2),
        ];
    }

    protected function heroAvailableSkills(
        User $user,
        ?string $roleKey = null,
        ?string $specializationKey = null,
        ?int $level = null,
    ): Collection
    {
        $user->loadMissing('unlockedSkills.learningCategory');

        $baseSkills = Skill::query()
            ->with('learningCategory')
            ->whereIn('slug', ['attaque-simple', 'defense-gardee'])
            ->get();

        return $this->filterSkillsForBuild(
            $baseSkills
                ->merge($user->unlockedSkills)
                ->unique('slug')
                ->values(),
            $roleKey,
            $specializationKey,
            $level ?? (int) $user->level,
        )
            ->sortBy(fn (Skill $skill) => [$this->slotWeight((string) $skill->slot_type), (int) ($skill->required_level ?? 1), $skill->id])
            ->values();
    }

    protected function companionAvailableSkills(
        UserCompanion $userCompanion,
        ?string $roleKey = null,
        ?string $specializationKey = null,
        ?int $level = null,
    ): Collection
    {
        $userCompanion->loadMissing([
            'companion.uniquePassiveSkill.learningCategory',
            'companion.signatureSkill.learningCategory',
            'unlockedSkills.learningCategory',
        ]);

        $treeSkills = UserSkill::query()
            ->with('skill.learningCategory')
            ->where('user_id', $userCompanion->user_id)
            ->where('owner_type', 'companion')
            ->where('owner_reference_id', $userCompanion->id)
            ->get()
            ->pluck('skill');

        return $this->filterSkillsForBuild(
            collect()
                ->merge($userCompanion->unlockedSkills)
                ->when($userCompanion->companion->uniquePassiveSkill, fn (Collection $skills) => $skills->push($userCompanion->companion->uniquePassiveSkill))
                ->when($userCompanion->companion->signatureSkill, fn (Collection $skills) => $skills->push($userCompanion->companion->signatureSkill))
                ->merge($treeSkills)
                ->unique('slug')
                ->values(),
            $roleKey,
            $specializationKey,
            $level ?? (int) $userCompanion->level,
        )
            ->sortBy(fn (Skill $skill) => [$this->slotWeight((string) $skill->slot_type), (int) ($skill->required_level ?? 1), $skill->id])
            ->values();
    }

    protected function buildSnapshot(
        string $name,
        string $subjectType,
        int $subjectReference,
        string $classKey,
        string $roleKey,
        string $specializationKey,
        array $actorTags,
        Collection $availableSkills,
        array $equippedSkills,
        array $loadout,
        int $level,
        ?string $specialTitle,
    ): array {
        $roleConfig = config('builds.roles.'.$roleKey, []);
        $specialization = $this->specializationConfig($classKey, $specializationKey) ?? [];
        $allSkills = array_values(array_merge(
            $equippedSkills['active'],
            $equippedSkills['passive'],
            array_filter([$equippedSkills['ultimate']]),
        ));

        return [
            'subject_key' => $subjectType === 'hero' ? 'hero' : 'companion:'.$subjectReference,
            'name' => $name,
            'title' => $specialTitle ?: $name,
            'class_key' => $classKey,
            'class_label' => $this->classLabel($classKey),
            'role_key' => $roleKey,
            'role_label' => $this->roleLabel($roleKey),
            'role_description' => (string) ($roleConfig['description'] ?? ''),
            'specialization_key' => $specializationKey,
            'specialization_label' => $this->specializationLabel($classKey, $specializationKey),
            'specialization_focus_tags' => collect($specialization['focus_tags'] ?? [])->map(fn (string $tag): array => [
                'key' => $tag,
                'label' => $this->tagLabel($tag),
            ])->all(),
            'stat_modifiers' => $this->combinedStatModifiers($roleKey, $classKey, $specializationKey, collect($equippedSkills['passive'])),
            'conditional_modifiers' => $this->conditionalModifiers(collect($equippedSkills['passive'])),
            'actor_tags' => array_values(array_unique(array_merge(
                $actorTags,
                [$roleKey],
                collect($allSkills)->pluck('tags')->flatten()->filter()->all(),
            ))),
            'available_skills' => $availableSkills->map(fn (Skill $skill): array => $this->skillPayload($skill))->values()->all(),
            'available_by_slot' => [
                'active' => $availableSkills->filter(fn (Skill $skill): bool => ($skill->slot_type ?? 'active') === 'active')->map(fn (Skill $skill): array => $this->skillPayload($skill))->values()->all(),
                'passive' => $availableSkills->filter(fn (Skill $skill): bool => ($skill->slot_type ?? 'active') === 'passive')->map(fn (Skill $skill): array => $this->skillPayload($skill))->values()->all(),
                'ultimate' => $availableSkills->filter(fn (Skill $skill): bool => ($skill->slot_type ?? 'active') === 'ultimate')->map(fn (Skill $skill): array => $this->skillPayload($skill))->values()->all(),
            ],
            'active_skills' => array_values(array_merge($equippedSkills['active'], array_filter([$equippedSkills['ultimate']]))),
            'passive_skills' => $equippedSkills['passive'],
            'ultimate_skill' => $equippedSkills['ultimate'],
            'all_skills' => $allSkills,
            'loadout' => $loadout,
            'available_slot_labels' => config('builds.loadout_limits'),
            'level' => $level,
        ];
    }

    protected function combinedStatModifiers(string $roleKey, string $classKey, string $specializationKey, Collection $passives): array
    {
        $totals = [
            'hp_multiplier' => 1.0,
            'mana_bonus' => 0,
            'attack_bonus' => 0,
            'magic_attack_bonus' => 0,
            'defense_bonus' => 0,
            'resistance_bonus' => 0,
            'speed_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'healing_power_multiplier' => 1.0,
            'damage_multiplier' => 1.0,
            'incoming_damage_multiplier' => 1.0,
        ];

        foreach ([
            config('builds.roles.'.$roleKey.'.stat_modifiers', []),
            config('builds.classes.'.$classKey.'.specializations.'.$specializationKey.'.stat_modifiers', []),
        ] as $modifierSet) {
            foreach ($modifierSet as $key => $value) {
                if (str_ends_with((string) $key, '_multiplier')) {
                    $totals[$key] = ($totals[$key] ?? 1.0) * (float) $value;
                } else {
                    $totals[$key] = ($totals[$key] ?? 0) + (int) $value;
                }
            }
        }

        foreach ($passives as $skill) {
            $effect = is_array($skill['combat_effect'] ?? null) ? $skill['combat_effect'] : [];
            foreach (['attack_bonus', 'magic_attack_bonus', 'defense_bonus', 'resistance_bonus', 'speed_bonus', 'precision_bonus', 'crit_bonus', 'mana_bonus'] as $stat) {
                $totals[$stat] += (int) ($effect[$stat] ?? 0);
            }

            foreach (['hp_multiplier', 'healing_power_multiplier', 'damage_multiplier', 'incoming_damage_multiplier'] as $stat) {
                if (isset($effect[$stat])) {
                    $totals[$stat] *= (float) $effect[$stat];
                }
            }
        }

        return $totals;
    }

    protected function conditionalModifiers(Collection $passives): array
    {
        return $passives
            ->map(fn (array $skill): array => is_array(data_get($skill, 'combat_effect.modifiers')) ? data_get($skill, 'combat_effect.modifiers') : [])
            ->flatten(1)
            ->values()
            ->all();
    }

    protected function normalizeLoadout(Collection $availableSkills, ?array $loadout): array
    {
        $loadout = is_array($loadout) ? $loadout : [];
        $limits = config('builds.loadout_limits');

        $bySlug = $availableSkills->keyBy('slug');
        $actives = collect($loadout['active'] ?? [])
            ->map(fn ($slug) => (string) $slug)
            ->filter(fn (string $slug): bool => $bySlug->has($slug) && ($bySlug[$slug]->slot_type ?? 'active') === 'active')
            ->unique()
            ->take((int) ($limits['active'] ?? 4))
            ->values();

        $passives = collect($loadout['passive'] ?? [])
            ->map(fn ($slug) => (string) $slug)
            ->filter(fn (string $slug): bool => $bySlug->has($slug) && ($bySlug[$slug]->slot_type ?? 'active') === 'passive')
            ->unique()
            ->take((int) ($limits['passive'] ?? 3))
            ->values();

        $ultimate = collect([(string) ($loadout['ultimate'] ?? '')])
            ->filter(fn (string $slug): bool => $slug !== '' && $bySlug->has($slug) && ($bySlug[$slug]->slot_type ?? 'active') === 'ultimate')
            ->first();

        if ($actives->isEmpty()) {
            $actives = $availableSkills
                ->filter(fn (Skill $skill): bool => ($skill->slot_type ?? 'active') === 'active')
                ->take((int) ($limits['active'] ?? 4))
                ->pluck('slug')
                ->values();
        }

        if ($ultimate === null) {
            $ultimate = $availableSkills
                ->first(fn (Skill $skill): bool => ($skill->slot_type ?? 'active') === 'ultimate')
                ?->slug;
        }

        return [
            'active' => $actives->all(),
            'passive' => $passives->all(),
            'ultimate' => $ultimate,
        ];
    }

    protected function equippedSkillsFromLoadout(Collection $availableSkills, array $loadout): array
    {
        $bySlug = $availableSkills->keyBy('slug');

        return [
            'active' => collect($loadout['active'] ?? [])
                ->map(fn (string $slug): ?array => $bySlug->has($slug) ? $this->skillPayload($bySlug[$slug]) : null)
                ->filter()
                ->values()
                ->all(),
            'passive' => collect($loadout['passive'] ?? [])
                ->map(fn (string $slug): ?array => $bySlug->has($slug) ? $this->skillPayload($bySlug[$slug]) : null)
                ->filter()
                ->values()
                ->all(),
            'ultimate' => ($loadout['ultimate'] ?? null) && $bySlug->has($loadout['ultimate'])
                ? $this->skillPayload($bySlug[$loadout['ultimate']])
                : null,
        ];
    }

    protected function skillPayload(Skill $skill): array
    {
        return array_merge([
            'id' => (int) $skill->id,
            'slug' => $skill->slug,
            'label' => $skill->name,
            'description' => $skill->description,
            'type' => $skill->type?->value ?? 'attack',
            'slot_type' => (string) ($skill->slot_type ?? 'active'),
            'power' => (int) ($skill->power ?? 0),
            'mana_cost' => (int) ($skill->energy_cost ?? 0),
            'cooldown' => (int) ($skill->cooldown ?? 0),
            'target' => $skill->target?->value ?? 'enemy',
            'animation' => $skill->animation_key,
            'theme_slug' => $skill->learningCategory?->slug,
            'range_type' => $skill->range_type,
            'damage_family' => $skill->damage_family,
            'resource_type' => $skill->resource_type,
            'combat_effect' => $skill->combat_effect ?? [],
            'scaling' => $skill->scaling ?? [],
            'tags' => $skill->tags ?? [],
            'required_level' => (int) ($skill->required_level ?? 1),
            'icon' => $skill->icon,
            'rarity' => $skill->rarity?->value,
            'role_keys' => $skill->role_keys ?? [],
            'specialization_keys' => $skill->specialization_keys ?? [],
            'tag_labels' => collect($skill->tags ?? [])->map(fn (string $tag): string => $this->tagLabel($tag))->values()->all(),
        ], $skill->combat_effect ?? []);
    }

    protected function filterSkillsForBuild(
        Collection $skills,
        ?string $roleKey,
        ?string $specializationKey,
        int $level,
    ): Collection {
        return $skills->filter(function (Skill $skill) use ($roleKey, $specializationKey, $level): bool {
            if ((int) ($skill->required_level ?? 1) > $level) {
                return false;
            }

            $roleKeys = collect($skill->role_keys ?? [])->filter()->values();
            if ($roleKeys->isNotEmpty() && ! $roleKeys->contains((string) $roleKey)) {
                return false;
            }

            $specializationKeys = collect($skill->specialization_keys ?? [])->filter()->values();
            if ($specializationKeys->isNotEmpty() && ! $specializationKeys->contains((string) $specializationKey)) {
                return false;
            }

            return true;
        })->values();
    }

    protected function specializationConfig(string $classKey, string $specializationKey): ?array
    {
        return config('builds.classes.'.$classKey.'.specializations.'.$specializationKey);
    }

    protected function synergyRequirementsMet(array $requirements, array $roles, array $actorTagsCount, array $skillTagsCount): bool
    {
        foreach (($requirements['roles'] ?? []) as $requiredRole) {
            if (! in_array($requiredRole, $roles, true)) {
                return false;
            }
        }

        foreach (($requirements['actor_tags'] ?? []) as $requiredActorTag) {
            if (($actorTagsCount[$requiredActorTag] ?? 0) <= 0) {
                return false;
            }
        }

        foreach (($requirements['actor_tags_count'] ?? []) as $tag => $count) {
            if (($actorTagsCount[$tag] ?? 0) < (int) $count) {
                return false;
            }
        }

        foreach (($requirements['skill_tags_count'] ?? []) as $tag => $count) {
            if (($skillTagsCount[$tag] ?? 0) < (int) $count) {
                return false;
            }
        }

        return true;
    }

    protected function effectTargetsActor(array $effect, array $actor): bool
    {
        if (($effect['target'] ?? 'all') === 'all') {
            return true;
        }

        $requiredTags = $effect['match_actor_tags'] ?? [];
        if ($requiredTags === []) {
            return true;
        }

        return collect($requiredTags)->every(fn (string $tag): bool => in_array($tag, $actor['actor_tags'], true));
    }

    protected function estimatePower(Collection $actors, array $synergies): int
    {
        $power = 0;

        foreach ($actors as $actor) {
            $modifiers = $actor['stat_modifiers'];
            $power += ($actor['level'] * 12);
            $power += (($modifiers['attack_bonus'] ?? 0) + ($modifiers['magic_attack_bonus'] ?? 0)) * 4;
            $power += (($modifiers['defense_bonus'] ?? 0) + ($modifiers['resistance_bonus'] ?? 0)) * 3;
            $power += (($modifiers['precision_bonus'] ?? 0) + ($modifiers['crit_bonus'] ?? 0)) * 2;
            $power += count($actor['active_skills']) * 10;
            $power += count($actor['passive_skills']) * 8;
            $power += $actor['ultimate_skill'] ? 18 : 0;
        }

        $power += count($synergies) * 14;

        return max(1, $power);
    }

    protected function strengths(Collection $actors, array $synergies): array
    {
        $strengths = collect();

        foreach ($actors as $actor) {
            $strengths->push($actor['role_label']);

            foreach ($actor['specialization_focus_tags'] as $tag) {
                $strengths->push($tag['label']);
            }
        }

        foreach ($synergies as $synergy) {
            $strengths->push($synergy['label']);
        }

        return $strengths->unique()->take(6)->values()->all();
    }

    protected function weaknesses(Collection $actors): array
    {
        $allTags = $actors->pluck('actor_tags')->flatten()->filter()->unique()->values();
        $allRoles = $actors->pluck('role_key')->filter()->unique()->values();
        $weaknesses = collect();

        if (! $allTags->contains('support') && ! $allRoles->contains('support')) {
            $weaknesses->push('Peu de soutien');
        }

        if (! $allTags->contains('control') && ! $allRoles->contains('control')) {
            $weaknesses->push('Peu de contrôle');
        }

        if (! $allTags->contains('ranged')) {
            $weaknesses->push('Portée limitée');
        }

        if (! $allRoles->contains('tank')) {
            $weaknesses->push('Frontline fragile');
        }

        return $weaknesses->take(4)->values()->all();
    }

    protected function slotWeight(string $slotType): int
    {
        return match ($slotType) {
            'active' => 1,
            'passive' => 2,
            'ultimate' => 3,
            default => 9,
        };
    }

    protected function defaultSelectedCompanionIds(User $user, Collection $companions): array
    {
        $persisted = collect($companions)
            ->sortBy(fn (UserCompanion $companion) => (int) ($companion->combat_position ?: 99))
            ->take((int) config('combat.party.max_companions', 2))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($persisted !== []) {
            return $persisted;
        }

        return $companions->take((int) config('combat.party.max_companions', 2))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
