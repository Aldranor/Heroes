<?php

namespace App\Services;

use App\Models\Companions\UserCompanion;
use App\Models\Creatures\Skill;
use App\Models\Learning\LearningCategory;
use App\Models\Skills\SkillNode;
use App\Models\Skills\SkillTree;
use App\Models\Skills\UserSkill;
use App\Models\Skills\UserSkillTreeProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SkillTreeService
{
    public function pageData(User $user, ?string $subjectKey = null, ?string $treeSlug = null, ?int $selectedNodeId = null): array
    {
        $subject = $this->resolveSubject($user, $subjectKey);

        $trees = SkillTree::query()
            ->with(['skillNodes.skill.learningCategory'])
            ->where('subject_type', $subject['type'])
            ->when($subject['tree_key'] !== null, fn ($query) => $query->where('subject_key', $subject['tree_key']))
            ->orderBy('id')
            ->get();

        $user->loadMissing([
            'skillTreeProgress.skillNode.skill.learningCategory',
            'userSkills.skill.learningCategory',
            'userCompanions.companion',
        ]);

        $progress = UserSkillTreeProgress::query()
            ->where('user_id', $user->id)
            ->where('owner_type', $subject['owner_type'])
            ->where('owner_reference_id', $subject['owner_reference_id'])
            ->get()
            ->keyBy('skill_node_id');
        $activeTree = $trees->firstWhere('slug', $treeSlug) ?? $trees->first();

        if (! $activeTree) {
            return [
                'subjects' => $this->subjects($user, $subject['key']),
                'activeSubject' => $subject,
                'skillTrees' => [],
                'activeTree' => null,
                'selectedNode' => null,
                'skillPoints' => (int) $subject['skill_points'],
                'specialSkillPoints' => (int) $subject['special_skill_points'],
                'userLevel' => (int) $subject['level'],
                'respecCost' => $this->respecCost($subject),
            ];
        }

        $nodeViews = $activeTree->skillNodes
            ->sortBy(fn (SkillNode $node) => [$node->column_index, $node->row_index, $node->sort_order, $node->id])
            ->map(function (SkillNode $node) use ($subject, $progress, $activeTree): array {
                $unlocked = $progress->has($node->id);
                $prerequisitesMet = $this->prerequisitesMet($node, $progress);
                $requiredLevel = max((int) $node->required_level, (int) ($node->skill->required_level ?? 1));
                $meetsLevel = (int) $subject['level'] >= $requiredLevel;

                return [
                    'id' => $node->id,
                    'slug' => $node->slug,
                    'name' => $node->skill->name,
                    'description' => $node->skill->description,
                    'type' => $node->skill->type?->value ?? 'attack',
                    'type_label' => $this->typeLabel($node->skill->type?->value ?? 'attack'),
                    'is_passive' => (bool) ($node->skill->is_passive ?? false),
                    'mode_label' => (bool) ($node->skill->is_passive ?? false) ? 'Passif' : 'Actif',
                    'icon' => $node->skill->icon,
                    'icon_label' => $this->iconLabel($node->skill->icon, $node->skill->name),
                    'rarity' => $node->skill->rarity?->value,
                    'rarity_label' => $this->rarityLabel($node->skill->rarity?->value),
                    'theme_name' => $node->skill->learningCategory?->name,
                    'theme_slug' => $node->skill->learningCategory?->slug,
                    'column' => (int) $node->column_index,
                    'row' => (int) $node->row_index,
                    'unlock_cost' => (int) $node->unlock_cost,
                    'special_cost' => (int) $node->special_cost,
                    'required_level' => $requiredLevel,
                    'state' => $unlocked ? 'unlocked' : ($prerequisitesMet && $meetsLevel ? 'available' : 'locked'),
                    'is_unlocked' => $unlocked,
                    'can_unlock' => ! $unlocked
                        && $prerequisitesMet
                        && $meetsLevel
                        && (int) $subject['skill_points'] >= (int) $node->unlock_cost
                        && (int) $subject['special_skill_points'] >= (int) $node->special_cost,
                    'prerequisite_ids' => collect($node->prerequisite_node_ids ?? [])->map(fn ($id) => (int) $id)->values()->all(),
                    'prerequisite_labels' => $this->prerequisiteLabels($node, $activeTree->skillNodes),
                    'effect_lines' => $this->effectLines($node->skill),
                    'slot_type' => (string) ($node->skill->slot_type ?? 'active'),
                    'slot_label' => $this->slotLabel((string) ($node->skill->slot_type ?? 'active')),
                    'class_label' => $this->classLabel($node->skill->specialization_keys ?? []),
                    'tags' => collect($node->skill->tags ?? [])->map(fn (string $tag): string => $this->tagLabel($tag))->values()->all(),
                ];
            })
            ->values();

        $selectedNode = $nodeViews->firstWhere('id', $selectedNodeId)
            ?? $nodeViews->firstWhere('state', 'available')
            ?? $nodeViews->first();

        return [
            'subjects' => $this->subjects($user, $subject['key']),
            'activeSubject' => $subject,
            'skillTrees' => $trees->map(fn (SkillTree $tree) => [
                'name' => $tree->name,
                'slug' => $tree->slug,
                'description' => $tree->description,
                'is_active' => $tree->is($activeTree),
                'class_label' => $tree->class_slug ? app(BuildService::class)->classLabel($tree->class_slug) : null,
                'role_label' => $tree->role_slug ? app(BuildService::class)->roleLabel($tree->role_slug) : null,
                'specialization_label' => $tree->class_slug && $tree->specialization_slug
                    ? app(BuildService::class)->specializationLabel($tree->class_slug, $tree->specialization_slug)
                    : null,
            ])->values()->all(),
            'activeTree' => [
                'id' => $activeTree->id,
                'name' => $activeTree->name,
                'slug' => $activeTree->slug,
                'description' => $activeTree->description,
                'icon' => $activeTree->icon,
                'class_label' => $activeTree->class_slug ? app(BuildService::class)->classLabel($activeTree->class_slug) : null,
                'role_label' => $activeTree->role_slug ? app(BuildService::class)->roleLabel($activeTree->role_slug) : null,
                'specialization_label' => $activeTree->class_slug && $activeTree->specialization_slug
                    ? app(BuildService::class)->specializationLabel($activeTree->class_slug, $activeTree->specialization_slug)
                    : null,
                'column_count' => max(1, (int) $nodeViews->max('column')),
                'row_count' => max(1, (int) $nodeViews->max('row')),
                'nodes' => $nodeViews->all(),
                'connections' => $this->connections($nodeViews),
                'unlocked_count' => $nodeViews->where('is_unlocked', true)->count(),
                'total_count' => $nodeViews->count(),
                'unlocked_percent' => $nodeViews->count() > 0
                    ? (int) round(($nodeViews->where('is_unlocked', true)->count() / $nodeViews->count()) * 100)
                    : 0,
            ],
            'selectedNode' => $selectedNode,
            'skillPoints' => (int) $subject['skill_points'],
            'specialSkillPoints' => (int) $subject['special_skill_points'],
            'userLevel' => (int) $subject['level'],
            'respecCost' => $this->respecCost($subject),
        ];
    }

    public function unlockNode(User $user, SkillNode $node, ?string $subjectKey = null): UserSkillTreeProgress
    {
        $subject = $this->resolveSubject($user, $subjectKey);

        return DB::transaction(function () use ($user, $node, $subject) {
            $lockedUser = User::query()
                ->with([
                    'skillTreeProgress',
                    'unlockedSkills',
                    'userCompanions',
                ])
                ->lockForUpdate()
                ->findOrFail($user->id);

            $lockedNode = SkillNode::query()
                ->with(['skillTree', 'skill.learningCategory'])
                ->findOrFail($node->id);

            if (($lockedNode->skillTree->subject_type ?? 'hero') !== $subject['type']) {
                throw new InvalidArgumentException('Ce nœud n’appartient pas à ce personnage.');
            }

            if ($subject['tree_key'] !== null && ($lockedNode->skillTree->subject_key ?? null) !== $subject['tree_key']) {
                throw new InvalidArgumentException('Ce nœud n’appartient pas à ce personnage.');
            }

            $lockedCompanion = null;
            if ($subject['type'] === 'companion') {
                $lockedCompanion = UserCompanion::query()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->findOrFail($subject['owner_reference_id']);
            }

            $progressRows = UserSkillTreeProgress::query()
                ->where('user_id', $lockedUser->id)
                ->where('owner_type', $subject['owner_type'])
                ->where('owner_reference_id', $subject['owner_reference_id'])
                ->get();

            $alreadyUnlocked = $progressRows
                ->contains(fn (UserSkillTreeProgress $progress): bool => (int) $progress->skill_node_id === (int) $lockedNode->id);

            if ($alreadyUnlocked) {
                throw new InvalidArgumentException('Cette compétence est déjà débloquée.');
            }

            $progress = $progressRows->keyBy('skill_node_id');
            if (! $this->prerequisitesMet($lockedNode, $progress)) {
                throw new InvalidArgumentException('Les prérequis de ce nœud ne sont pas remplis.');
            }

            $requiredLevel = max((int) $lockedNode->required_level, (int) ($lockedNode->skill->required_level ?? 1));
            $subjectLevel = $subject['type'] === 'hero'
                ? (int) $lockedUser->level
                : (int) ($lockedCompanion?->level ?? 1);

            if ($subjectLevel < $requiredLevel) {
                throw new InvalidArgumentException("Niveau {$requiredLevel} requis pour débloquer cette compétence.");
            }

            $skillPoints = $subject['type'] === 'hero'
                ? (int) $lockedUser->skill_points
                : (int) ($lockedCompanion?->talent_points ?? 0);
            $specialSkillPoints = $subject['type'] === 'hero'
                ? (int) $lockedUser->special_skill_points
                : (int) ($lockedCompanion?->special_talent_points ?? 0);

            if ($skillPoints < (int) $lockedNode->unlock_cost) {
                throw new InvalidArgumentException('Pas assez de points de compétence.');
            }

            if ($specialSkillPoints < (int) $lockedNode->special_cost) {
                throw new InvalidArgumentException('Pas assez de points spéciaux.');
            }

            if ($subject['type'] === 'hero') {
                $lockedUser->skill_points -= (int) $lockedNode->unlock_cost;
                $lockedUser->special_skill_points -= (int) $lockedNode->special_cost;
                $lockedUser->save();
            } else {
                $lockedCompanion->talent_points -= (int) $lockedNode->unlock_cost;
                $lockedCompanion->special_talent_points -= (int) $lockedNode->special_cost;
                $lockedCompanion->save();
            }

            UserSkill::query()->firstOrCreate(
                [
                    'user_id' => $lockedUser->id,
                    'owner_type' => $subject['owner_type'],
                    'owner_reference_id' => $subject['owner_reference_id'],
                    'skill_id' => $lockedNode->skill_id,
                ],
                [
                    'skill_node_id' => $lockedNode->id,
                    'source' => 'skill_tree',
                    'unlocked_at' => now(),
                ],
            );

            return UserSkillTreeProgress::query()->create([
                'user_id' => $lockedUser->id,
                'owner_type' => $subject['owner_type'],
                'owner_reference_id' => $subject['owner_reference_id'],
                'skill_tree_id' => $lockedNode->skill_tree_id,
                'skill_node_id' => $lockedNode->id,
                'spent_skill_points' => (int) $lockedNode->unlock_cost,
                'spent_special_points' => (int) $lockedNode->special_cost,
                'unlocked_at' => now(),
            ]);
        });
    }

    public function respec(User $user, ?string $subjectKey = null): void
    {
        $subject = $this->resolveSubject($user, $subjectKey);

        DB::transaction(function () use ($user, $subject): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedCompanion = null;

            if ($subject['type'] === 'companion') {
                $lockedCompanion = UserCompanion::query()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->findOrFail($subject['owner_reference_id']);
            }

            $progressRows = UserSkillTreeProgress::query()
                ->where('user_id', $lockedUser->id)
                ->where('owner_type', $subject['owner_type'])
                ->where('owner_reference_id', $subject['owner_reference_id'])
                ->get();

            if ($progressRows->isEmpty()) {
                return;
            }

            $refundSkillPoints = (int) $progressRows->sum('spent_skill_points');
            $refundSpecialPoints = (int) $progressRows->sum('spent_special_points');

            if ($subject['type'] === 'hero') {
                if ((int) $lockedUser->free_respecs > 0) {
                    $lockedUser->free_respecs -= 1;
                } else {
                    $cost = (int) config('builds.respec.hero_coin_cost', 150);
                    if ((int) $lockedUser->coins < $cost) {
                        throw new InvalidArgumentException('Pas assez de ressources pour réinitialiser le héros.');
                    }
                    $lockedUser->coins -= $cost;
                }

                $lockedUser->skill_points += $refundSkillPoints;
                $lockedUser->special_skill_points += $refundSpecialPoints;
                $lockedUser->combat_loadout = null;
                $lockedUser->save();
            } else {
                if ((int) ($lockedCompanion?->free_respecs ?? 0) > 0) {
                    $lockedCompanion->free_respecs -= 1;
                } else {
                    $cost = (int) config('builds.respec.companion_coin_cost', 90);
                    if ((int) $lockedUser->coins < $cost) {
                        throw new InvalidArgumentException('Pas assez de ressources pour réinitialiser ce compagnon.');
                    }
                    $lockedUser->coins -= $cost;
                    $lockedUser->save();
                }

                $lockedCompanion->talent_points += $refundSkillPoints;
                $lockedCompanion->special_talent_points += $refundSpecialPoints;
                $lockedCompanion->combat_loadout = null;
                $lockedCompanion->save();
            }

            $skillIds = UserSkill::query()
                ->where('user_id', $lockedUser->id)
                ->where('owner_type', $subject['owner_type'])
                ->where('owner_reference_id', $subject['owner_reference_id'])
                ->pluck('skill_id');

            UserSkill::query()
                ->where('user_id', $lockedUser->id)
                ->where('owner_type', $subject['owner_type'])
                ->where('owner_reference_id', $subject['owner_reference_id'])
                ->delete();

            UserSkillTreeProgress::query()
                ->where('user_id', $lockedUser->id)
                ->where('owner_type', $subject['owner_type'])
                ->where('owner_reference_id', $subject['owner_reference_id'])
                ->delete();

            if ($subject['type'] === 'companion' && $skillIds->isNotEmpty()) {
                UserSkill::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('owner_type', 'companion')
                    ->where('owner_reference_id', $subject['owner_reference_id'])
                    ->whereIn('skill_id', $skillIds)
                    ->delete();
            }
        });
    }

    public function combatSkillsForUser(User $user): Collection
    {
        $user->loadMissing('unlockedSkills.learningCategory');

        return $user->unlockedSkills
            ->filter(fn (Skill $skill): bool => ! (bool) $skill->is_passive)
            ->map(fn (Skill $skill): array => $this->combatPayload($skill))
            ->values();
    }

    public function passiveCombatBonusesForUser(User $user, ?LearningCategory $learningCategory = null): array
    {
        $user->loadMissing('unlockedSkills.learningCategory');

        $totals = [
            'attack_bonus' => 0,
            'defense_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'incoming_damage_multiplier' => 1.0,
            'labels' => [],
        ];

        foreach ($user->unlockedSkills as $skill) {
            if (! $skill->is_passive) {
                continue;
            }

            $effect = is_array($skill->combat_effect) ? $skill->combat_effect : [];
            $themeMatches = $learningCategory
                && $skill->learningCategory
                && $learningCategory->is($skill->learningCategory);

            $totals['attack_bonus'] += (int) ($effect['attack_bonus'] ?? 0);
            $totals['defense_bonus'] += (int) ($effect['defense_bonus'] ?? 0);
            $totals['precision_bonus'] += (int) ($effect['precision_bonus'] ?? 0);
            $totals['crit_bonus'] += (int) ($effect['crit_bonus'] ?? 0);
            if (isset($effect['incoming_damage_multiplier'])) {
                $totals['incoming_damage_multiplier'] *= (float) $effect['incoming_damage_multiplier'];
            }

            if ($themeMatches) {
                $totals['attack_bonus'] += (int) ($effect['themed_attack_bonus'] ?? 0);
                $totals['precision_bonus'] += (int) ($effect['themed_precision_bonus'] ?? 0);
            }

            $totals['labels'][] = $skill->name;
        }

        return $totals;
    }

    public function combatSkillsForCompanion(UserCompanion $userCompanion): Collection
    {
        return UserSkill::query()
            ->with('skill.learningCategory')
            ->where('user_id', $userCompanion->user_id)
            ->where('owner_type', 'companion')
            ->where('owner_reference_id', $userCompanion->id)
            ->get()
            ->pluck('skill')
            ->filter(fn (Skill $skill): bool => ! (bool) $skill->is_passive)
            ->map(fn (Skill $skill): array => $this->combatPayload($skill))
            ->values();
    }

    public function passiveCombatBonusesForCompanion(UserCompanion $userCompanion, ?LearningCategory $learningCategory = null): array
    {
        $skills = UserSkill::query()
            ->with('skill.learningCategory')
            ->where('user_id', $userCompanion->user_id)
            ->where('owner_type', 'companion')
            ->where('owner_reference_id', $userCompanion->id)
            ->get()
            ->pluck('skill');

        $totals = [
            'attack_bonus' => 0,
            'defense_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'incoming_damage_multiplier' => 1.0,
            'labels' => [],
        ];

        foreach ($skills as $skill) {
            if (! $skill->is_passive) {
                continue;
            }

            $effect = is_array($skill->combat_effect) ? $skill->combat_effect : [];
            $themeMatches = $learningCategory
                && $skill->learningCategory
                && $learningCategory->is($skill->learningCategory);

            $totals['attack_bonus'] += (int) ($effect['attack_bonus'] ?? 0);
            $totals['defense_bonus'] += (int) ($effect['defense_bonus'] ?? 0);
            $totals['precision_bonus'] += (int) ($effect['precision_bonus'] ?? 0);
            $totals['crit_bonus'] += (int) ($effect['crit_bonus'] ?? 0);
            if (isset($effect['incoming_damage_multiplier'])) {
                $totals['incoming_damage_multiplier'] *= (float) $effect['incoming_damage_multiplier'];
            }

            if ($themeMatches) {
                $totals['attack_bonus'] += (int) ($effect['themed_attack_bonus'] ?? 0);
                $totals['precision_bonus'] += (int) ($effect['themed_precision_bonus'] ?? 0);
            }

            $totals['labels'][] = $skill->name;
        }

        return $totals;
    }

    public function grantStartingSkills(User $user, array $slugs): void
    {
        $skills = Skill::query()->whereIn('slug', $slugs)->get()->keyBy('slug');

        foreach ($slugs as $slug) {
            if (! $skills->has($slug)) {
                continue;
            }

            UserSkill::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'owner_type' => 'hero',
                    'owner_reference_id' => 0,
                    'skill_id' => $skills[$slug]->id,
                ],
                [
                    'source' => 'grant',
                    'unlocked_at' => now(),
                ],
            );
        }
    }

    protected function combatPayload(Skill $skill): array
    {
        return array_merge([
            'slug' => $skill->slug,
            'label' => $skill->name,
            'description' => $skill->description,
            'type' => $skill->type?->value ?? 'attack',
            'power' => (int) ($skill->power ?? 0),
            'mana_cost' => (int) ($skill->energy_cost ?? 0),
            'cooldown' => (int) ($skill->cooldown ?? 0),
            'target' => $skill->target?->value ?? 'enemy',
            'animation' => $skill->animation_key,
            'theme_slug' => $skill->learningCategory?->slug,
        ], $skill->combat_effect ?? []);
    }

    protected function prerequisitesMet(SkillNode $node, Collection $progress): bool
    {
        $requiredNodeIds = collect($node->prerequisite_node_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($requiredNodeIds->isEmpty()) {
            return true;
        }

        return $requiredNodeIds->every(fn (int $id): bool => $progress->has($id));
    }

    protected function prerequisiteLabels(SkillNode $node, Collection $treeNodes): array
    {
        $requiredIds = collect($node->prerequisite_node_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($requiredIds->isEmpty()) {
            return [];
        }

        $nodes = $treeNodes->keyBy('id');

        return $requiredIds
            ->map(fn (int $id): ?string => $nodes->get($id)?->skill?->name)
            ->filter()
            ->values()
            ->all();
    }

    protected function connections(Collection $nodes): array
    {
        $byId = $nodes->keyBy('id');
        $colCount = max(1, (int) $nodes->max('column'));
        $rowCount = max(1, (int) $nodes->max('row'));

        return $nodes
            ->flatMap(function (array $node) use ($byId, $colCount, $rowCount): array {
                return collect($node['prerequisite_ids'] ?? [])
                    ->map(function (int $sourceId) use ($byId, $node, $colCount, $rowCount): ?array {
                        $source = $byId->get($sourceId);
                        if (! $source) {
                            return null;
                        }

                        $sourceX = $this->gridPercent((int) $source['column'], $colCount);
                        $sourceY = $this->gridPercent((int) $source['row'], $rowCount);
                        $targetX = $this->gridPercent((int) $node['column'], $colCount);
                        $targetY = $this->gridPercent((int) $node['row'], $rowCount);
                        [$trimmedSourceX, $trimmedSourceY, $trimmedTargetX, $trimmedTargetY] = $this->trimConnection(
                            $sourceX,
                            $sourceY,
                            $targetX,
                            $targetY,
                        );

                        return [
                            'source_x' => $trimmedSourceX,
                            'source_y' => $trimmedSourceY,
                            'target_x' => $trimmedTargetX,
                            'target_y' => $trimmedTargetY,
                            'is_active' => $source['is_unlocked'] && $node['is_unlocked'],
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    protected function gridPercent(int $position, int $max): float
    {
        if ($max <= 1) {
            return 50.0;
        }

        return ((($position - 0.5) / $max) * 100);
    }

    protected function trimConnection(float $sourceX, float $sourceY, float $targetX, float $targetY): array
    {
        $deltaX = $targetX - $sourceX;
        $deltaY = $targetY - $sourceY;
        $distance = sqrt(($deltaX ** 2) + ($deltaY ** 2));

        if ($distance <= 0.001) {
            return [$sourceX, $sourceY, $targetX, $targetY];
        }

        $trim = min(4.5, $distance / 2);
        $offsetX = ($deltaX / $distance) * $trim;
        $offsetY = ($deltaY / $distance) * $trim;

        return [
            $sourceX + $offsetX,
            $sourceY + $offsetY,
            $targetX - $offsetX,
            $targetY - $offsetY,
        ];
    }

    protected function resolveSubject(User $user, ?string $subjectKey): array
    {
        $normalized = $subjectKey ?: 'hero';

        if ($normalized === 'hero') {
            return [
                'key' => 'hero',
                'type' => 'hero',
                'tree_key' => 'hero',
                'owner_type' => 'hero',
                'owner_reference_id' => 0,
                'label' => 'Héros',
                'name' => $user->avatar?->nickname ?? $user->name,
                'level' => (int) $user->level,
                'skill_points' => (int) ($user->skill_points ?? 0),
                'special_skill_points' => (int) ($user->special_skill_points ?? 0),
                'free_respecs' => (int) ($user->free_respecs ?? 0),
            ];
        }

        if (! str_starts_with($normalized, 'companion:')) {
            throw new InvalidArgumentException('Sujet de talents invalide.');
        }

        $companionId = (int) substr($normalized, strlen('companion:'));
        $companion = $user->userCompanions()->with('companion')->findOrFail($companionId);

        return [
            'key' => 'companion:'.$companion->id,
            'type' => 'companion',
            'tree_key' => $companion->companion->slug,
            'owner_type' => 'companion',
            'owner_reference_id' => (int) $companion->id,
            'label' => 'Compagnon',
            'name' => $companion->companion->name,
            'level' => (int) $companion->level,
            'skill_points' => (int) ($companion->talent_points ?? 0),
            'special_skill_points' => (int) ($companion->special_talent_points ?? 0),
            'free_respecs' => (int) ($companion->free_respecs ?? 0),
        ];
    }

    protected function subjects(User $user, string $activeKey): array
    {
        $user->loadMissing('userCompanions.companion');

        return collect([
            [
                'key' => 'hero',
                'label' => 'Héros',
                'name' => $user->avatar?->nickname ?? $user->name,
                'is_active' => $activeKey === 'hero',
            ],
        ])
            ->merge($user->userCompanions->map(fn (UserCompanion $companion): array => [
                'key' => 'companion:'.$companion->id,
                'label' => 'Compagnon',
                'name' => $companion->companion->name,
                'is_active' => $activeKey === 'companion:'.$companion->id,
            ]))
            ->values()
            ->all();
    }

    protected function respecCost(array $subject): int
    {
        return $subject['type'] === 'hero'
            ? (int) config('builds.respec.hero_coin_cost', 150)
            : (int) config('builds.respec.companion_coin_cost', 90);
    }

    protected function effectLines(Skill $skill): array
    {
        $effect = is_array($skill->combat_effect) ? $skill->combat_effect : [];
        $lines = [];

        if ((bool) ($skill->is_passive ?? false)) {
            $lines[] = 'Effet passif toujours actif une fois débloqué.';
        }

        if ((int) ($skill->power ?? 0) > 0) {
            $lines[] = match ($skill->type?->value) {
                'area_attack' => 'Inflige '.$skill->power.' dégâts à tous les ennemis.',
                'heal' => 'Restaure '.$skill->power.' points de vie.',
                default => 'Inflige '.$skill->power.' dégâts de base.',
            };
        }

        if ((int) ($skill->energy_cost ?? 0) > 0) {
            $lines[] = 'Coût énergie : '.$skill->energy_cost;
        }

        if ((int) ($skill->cooldown ?? 0) > 0) {
            $lines[] = 'Recharge : '.$skill->cooldown.' tour(s).';
        }

        if ((int) ($effect['attack_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus attaque : '.((int) $effect['attack_bonus'] > 0 ? '+' : '').$effect['attack_bonus'];
        }

        if ((int) ($effect['magic_attack_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus magie : '.((int) $effect['magic_attack_bonus'] > 0 ? '+' : '').$effect['magic_attack_bonus'];
        }

        if ((int) ($effect['defense_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus défense : '.((int) $effect['defense_bonus'] > 0 ? '+' : '').$effect['defense_bonus'];
        }

        if ((int) ($effect['resistance_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus résistance : '.((int) $effect['resistance_bonus'] > 0 ? '+' : '').$effect['resistance_bonus'];
        }

        if ((int) ($effect['precision_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus précision : '.((int) $effect['precision_bonus'] > 0 ? '+' : '').$effect['precision_bonus'];
        }

        if ((int) ($effect['crit_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus critique : '.((int) $effect['crit_bonus'] > 0 ? '+' : '').$effect['crit_bonus'];
        }

        if ((int) ($effect['speed_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus vitesse : '.((int) $effect['speed_bonus'] > 0 ? '+' : '').$effect['speed_bonus'];
        }

        if ((int) ($effect['mana_bonus'] ?? 0) !== 0) {
            $lines[] = 'Bonus énergie : '.((int) $effect['mana_bonus'] > 0 ? '+' : '').$effect['mana_bonus'];
        }

        if ((int) ($effect['themed_attack_bonus'] ?? 0) > 0 && $skill->learningCategory) {
            $lines[] = 'Bonus supplémentaire sur le thème '.$skill->learningCategory->name.' : +'.$effect['themed_attack_bonus'].' attaque';
        }

        if ((int) ($effect['duration_rounds'] ?? 0) > 0) {
            $lines[] = 'Durée : '.$effect['duration_rounds'].' tour(s).';
        }

        if (isset($effect['incoming_damage_multiplier'])) {
            $reduction = max(0, (int) round((1 - (float) $effect['incoming_damage_multiplier']) * 100));
            $lines[] = 'Réduit les dégâts subis de '.$reduction.'%.';
        }

        if (isset($effect['hp_multiplier'])) {
            $bonus = (int) round((((float) $effect['hp_multiplier']) - 1) * 100);
            $lines[] = ($bonus >= 0 ? '+' : '').$bonus.'% PV maximum.';
        }

        if ((int) ($effect['penalty_reduction'] ?? 0) > 0) {
            $lines[] = 'Réduit les pénalités subies de '.$effect['penalty_reduction'].'%.';
        }

        if ((int) ($effect['answer_elimination_count'] ?? 0) > 0) {
            $lines[] = 'Peut éliminer '.$effect['answer_elimination_count'].' mauvaise réponse pendant une séquence de combat.';
        }

        if ((int) ($effect['bonus_time_seconds'] ?? 0) > 0) {
            $lines[] = 'Accorde +'.$effect['bonus_time_seconds'].' seconde(s) sur une question difficile.';
        }

        if ((int) ($effect['streak_required'] ?? 0) > 0 && (int) ($effect['streak_attack_bonus'] ?? 0) > 0) {
            $lines[] = 'Après '.$effect['streak_required'].' bonnes réponses d’affilée : +'.$effect['streak_attack_bonus'].' attaque.';
        }

        foreach (($effect['modifiers'] ?? []) as $modifier) {
            if (! is_array($modifier)) {
                continue;
            }

            $tags = collect($modifier['match_skill_tags'] ?? [])
                ->filter()
                ->map(fn (string $tag): string => $this->tagLabel($tag))
                ->implode(', ');

            $scope = $tags !== '' ? ' sur '.$tags : '';

            if (($modifier['stat'] ?? null) === 'damage_multiplier' && isset($modifier['value'])) {
                $bonus = (int) round((((float) $modifier['value']) - 1) * 100);
                $lines[] = ($bonus >= 0 ? '+' : '').$bonus.'% dégâts'.$scope.'.';
                continue;
            }

            if (($modifier['stat'] ?? null) === 'healing_multiplier' && isset($modifier['value'])) {
                $bonus = (int) round((((float) $modifier['value']) - 1) * 100);
                $lines[] = ($bonus >= 0 ? '+' : '').$bonus.'% soins'.$scope.'.';
                continue;
            }

            if (($modifier['stat'] ?? null) === 'cooldown_refund_on_crit' && isset($modifier['value'])) {
                $lines[] = 'Les critiques réduisent les cooldowns de '.$modifier['value'].' tour(s)'.$scope.'.';
                continue;
            }

            if (($modifier['stat'] ?? null) === 'resource_on_shield' && isset($modifier['value'])) {
                $lines[] = 'Les boucliers rendent '.$modifier['value'].' énergie'.$scope.'.';
                continue;
            }

            if (($modifier['stat'] ?? null) === 'apply_status' && isset($modifier['status']) && is_array($modifier['status'])) {
                $lines[] = 'Applique '.$modifier['status']['label'].''.$scope.'.';
            }
        }

        return $lines;
    }

    protected function iconLabel(?string $icon, string $fallbackName): string
    {
        return match ($icon) {
            'sword' => 'ATK',
            'shield' => 'DEF',
            'sparkles' => 'BUF',
            'heart' => 'SOI',
            'target' => 'DEB',
            'burst' => 'AOE',
            'compass' => 'THE',
            'mind' => 'RES',
            default => strtoupper(substr($fallbackName, 0, 2)),
        };
    }

    protected function slotLabel(string $slotType): string
    {
        return match ($slotType) {
            'passive' => 'Passif',
            'ultimate' => 'Ultime',
            default => 'Actif',
        };
    }

    protected function classLabel(array $specializationKeys): ?string
    {
        $first = collect($specializationKeys)->first();

        if (! $first) {
            return null;
        }

        foreach (config('builds.classes', []) as $classKey => $classConfig) {
            if (isset($classConfig['specializations'][$first])) {
                return app(BuildService::class)->classLabel($classKey).' · '.app(BuildService::class)->specializationLabel($classKey, (string) $first);
            }
        }

        return ucfirst(str_replace('-', ' ', (string) $first));
    }

    protected function tagLabel(string $tag): string
    {
        return (string) config('builds.tag_labels.'.$tag, ucfirst($tag));
    }

    protected function typeLabel(string $type): string
    {
        return match ($type) {
            'attack' => 'Attaque',
            'heal' => 'Soin',
            'shield', 'defense' => 'Bouclier',
            'buff' => 'Buff',
            'debuff' => 'Debuff',
            'area_attack' => 'Zone',
            'penalty_reduction' => 'Réduction',
            'theme_bonus' => 'Thématique',
            default => 'Technique',
        };
    }

    protected function rarityLabel(?string $rarity): ?string
    {
        return match ($rarity) {
            'common' => 'Commun',
            'rare' => 'Rare',
            'epic' => 'Épique',
            'legendary' => 'Légendaire',
            default => null,
        };
    }
}
