<?php

namespace App\Services;

use App\Enums\BattleActorType;
use App\Enums\BattleStatus;
use App\Models\Battle\Battle;
use App\Models\Companions\UserCompanion;
use App\Models\Creatures\Skill;
use App\Models\Learning\UserLearningItemProgress;
use App\Models\Lessons\UserLessonProgress;
use App\Models\User;
use App\Models\World\AdventureNode;
use App\Models\World\Enemy;
use App\Support\CombatViewFactory;
use App\Support\CraftpixSprite;
use App\Support\LpcSprite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CombatService
{
    public function __construct(
        protected AdventureMapService $adventureMapService,
        protected RewardService $rewardService,
        protected SkillTreeService $skillTreeService,
        protected BuildService $buildService,
        protected CombatViewFactory $combatViewFactory,
    ) {}

    public function startForNode(User $user, AdventureNode $node, array $companionIds = []): Battle
    {
        $node->loadMissing(['map.world.learningDomain', 'learningCategory', 'lesson']);
        $this->adventureMapService->assertNodeAvailableForUser($user, $node);

        if (! in_array($node->node_type, ['combat_normal', 'combat_elite', 'boss'], true)) {
            throw new InvalidArgumentException('Ce nœud ne lance pas encore de combat.');
        }

        $companions = $this->resolvePartyCompanions($user, $companionIds, $node);
        $enemies = $this->resolveEnemiesForNode($node);
        $learningProfile = $this->learningProfile($user, $node);
        $actors = $this->buildActors($user, $companions, $enemies, $node, $learningProfile);
        $turnQueue = $this->buildTurnQueue($actors);
        $currentActorKey = $turnQueue[0] ?? null;

        if (! $currentActorKey) {
            throw new InvalidArgumentException('Impossible de lancer un combat sans combattants actifs.');
        }

        $battle = Battle::query()->create([
            'user_id' => $user->id,
            'enemy_id' => (int) $enemies->first()?->id,
            'adventure_node_id' => $node->id,
            'status' => BattleStatus::Ongoing,
            'party' => [
                'hero_user_id' => $user->id,
                'user_companion_ids' => $companions->pluck('id')->values()->all(),
            ],
            'state' => [
                'round' => 1,
                'turn_queue' => $turnQueue,
                'current_turn_index' => 0,
                'current_actor_key' => $currentActorKey,
                'actors' => $actors,
                'logs' => [[
                    'tone' => 'amber',
                    'text' => $this->startLogText($node, $enemies),
                ]],
                'phase_index' => 0,
                'last_action' => null,
            ],
            'metadata' => [
                'context' => [
                    'world_name' => $node->map?->world?->name,
                    'map_name' => $node->map?->name,
                    'node_title' => $node->title,
                    'node_type' => $node->node_type,
                    'category_name' => $node->learningCategory?->name,
                    'background' => asset(config('combat.backgrounds.'.$node->node_type, 'images/background-combat/battleback9.png')),
                ],
                'academic' => $learningProfile,
                'rewards' => $this->rewardPreview($enemies, $node),
            ],
            'started_at' => now(),
        ]);

        $this->adventureMapService->markMissionBattleStarted($user, $node, $battle);

        return $this->autoAdvanceAiTurns($battle->refresh());
    }

    public function viewData(Battle $battle): array
    {
        $battle->loadMissing(['enemy', 'adventureNode.map.world.learningDomain']);

        $state = $battle->state ?? [];
        $state['actors'] = collect($state['actors'] ?? [])
            ->map(fn (array $actor): array => $this->refreshActorVisuals($actor))
            ->all();
        $actors = collect($state['actors'] ?? []);
        $currentActor = $state['current_actor_key'] ? $actors->get($state['current_actor_key']) : null;
        $playerActors = $actors
            ->filter(fn (array $actor): bool => ($actor['side'] ?? null) === 'player')
            ->sortBy('layer_order')
            ->values();
        $enemyActors = $actors
            ->filter(fn (array $actor): bool => ($actor['side'] ?? null) === 'enemy')
            ->sortBy('layer_order')
            ->values();
        $context = $battle->metadata['context'] ?? [];
        $nodeType = $context['node_type'] ?? $battle->adventureNode?->node_type;
        $context['background'] = asset(config('combat.backgrounds.'.$nodeType, 'images/background-combat/battleback9.png'));
        $rewards = $battle->metadata['completed_rewards'] ?? $battle->metadata['rewards'] ?? [];
        $academic = $battle->metadata['academic'] ?? [];
        $backRoute = $battle->adventureNode
            ? route('missions.index', [
                'world' => $battle->adventureNode->map?->world_id,
                'map' => $battle->adventureNode->map?->id,
                'node' => $battle->adventureNode->id,
            ])
            : route('missions.index');

        return $this->combatViewFactory->decorate([
            'battle' => $battle,
            'state' => $state,
            'context' => $context,
            'rewards' => $rewards,
            'academic' => $academic,
            'playerActors' => $playerActors,
            'enemyActors' => $enemyActors,
            'currentActor' => $currentActor,
            'lastAction' => $state['last_action'] ?? [],
            'turnActions' => $state['turn_actions'] ?? [],
            'backRoute' => $backRoute,
            'availableSkills' => collect($currentActor['skills'] ?? [])->filter(
                fn (array $skill): bool => $this->skillAvailable($currentActor, $skill),
            )->values(),
            'livingEnemies' => $enemyActors->filter(fn (array $actor): bool => ($actor['current_hp'] ?? 0) > 0)->values(),
            'livingAllies' => $playerActors->filter(fn (array $actor): bool => ($actor['current_hp'] ?? 0) > 0)->values(),
            'battleSummary' => [
                'round' => (int) ($state['round'] ?? 1),
                'phase_label' => data_get($state, 'boss_phase.label'),
                'logs' => collect($state['logs'] ?? [])->take(-6)->values(),
                'winner' => $battle->status === BattleStatus::Won ? 'player' : ($battle->status === BattleStatus::Lost ? 'enemy' : null),
            ],
        ]);
    }

    public function performPlayerAction(Battle $battle, User $user, string $skillSlug, ?string $targetKey = null): Battle
    {
        return DB::transaction(function () use ($battle, $user, $skillSlug, $targetKey) {
            $lockedBattle = Battle::query()
                ->with(['enemy', 'adventureNode.map.world.learningDomain'])
                ->lockForUpdate()
                ->findOrFail($battle->id);

            if ($lockedBattle->user_id !== $user->id) {
                abort(404);
            }

            if ($lockedBattle->status !== BattleStatus::Ongoing) {
                throw new InvalidArgumentException('Ce combat est déjà terminé.');
            }

            $state = $lockedBattle->state ?? [];
            $currentActorKey = $state['current_actor_key'] ?? null;
            $currentActor = $currentActorKey ? data_get($state, "actors.{$currentActorKey}") : null;

            if (! $currentActor || ($currentActor['side'] ?? null) !== 'player') {
                throw new InvalidArgumentException('Ce n’est pas au joueur d’agir.');
            }

            $skill = collect($currentActor['skills'] ?? [])->firstWhere('slug', $skillSlug);

            if (! $skill || ! $this->skillAvailable($currentActor, $skill)) {
                throw new InvalidArgumentException('Cette compétence n’est pas disponible.');
            }

            $resolvedTargetKey = $this->resolveTargetKey($state, $currentActorKey, $skill, $targetKey);

            // Reset the per-turn replay log so the front-end only sees the
            // actions that ran during this submit cycle.
            $state['turn_actions'] = [];

            $result = $this->applySkill($lockedBattle, $state, $currentActorKey, $skill, $resolvedTargetKey);
            $this->recordTurn($lockedBattle, $currentActor, $skill['slug'], $result);

            if ($this->battleEnded($state)) {
                return $this->finalizeBattle($lockedBattle, $state);
            }

            $this->advanceTurn($state);
            $lockedBattle->forceFill(['state' => $state])->save();

            return $this->autoAdvanceAiTurns($lockedBattle->refresh());
        });
    }

    protected function autoAdvanceAiTurns(Battle $battle): Battle
    {
        return DB::transaction(function () use ($battle) {
            $lockedBattle = Battle::query()
                ->with(['enemy', 'adventureNode.map.world.learningDomain'])
                ->lockForUpdate()
                ->findOrFail($battle->id);

            if ($lockedBattle->status !== BattleStatus::Ongoing) {
                return $lockedBattle->refresh();
            }

            $state = $lockedBattle->state ?? [];

            while (($state['current_actor_key'] ?? null) && ($state['actors'][$state['current_actor_key']]['side'] ?? null) === 'enemy') {
                $actorKey = $state['current_actor_key'];
                $actor = $state['actors'][$actorKey];
                $decision = $this->resolveEnemyDecision($state, $actorKey, $actor);
                $result = $this->applySkill($lockedBattle, $state, $actorKey, $decision['skill'], $decision['target_key']);
                $this->recordTurn($lockedBattle, $actor, $decision['skill']['slug'], $result);

                if ($this->battleEnded($state)) {
                    return $this->finalizeBattle($lockedBattle, $state);
                }

                $this->advanceTurn($state);
            }

            $lockedBattle->forceFill(['state' => $state])->save();

            return $lockedBattle->refresh();
        });
    }

    protected function resolvePartyCompanions(User $user, array $companionIds, AdventureNode $node): Collection
    {
        $maxCompanions = (int) config('combat.party.max_companions', 2);
        $ids = collect($companionIds)
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->take($maxCompanions)
            ->values();

        $companions = $user->userCompanions()
            ->with([
                'companion.specializationCategory',
                'companion.skills',
                'unlockedSkills',
            ])
            ->orderByDesc('is_favorite')
            ->orderBy('id')
            ->get();

        if ($companions->isEmpty()) {
            throw new InvalidArgumentException('Aucun compagnon n’est disponible pour ce combat.');
        }

        $selected = $ids->isNotEmpty()
            ? $companions->whereIn('id', $ids)->values()
            : $companions->take($maxCompanions)->values();

        if ($selected->isEmpty()) {
            throw new InvalidArgumentException('Les compagnons choisis ne sont pas valides.');
        }

        return $selected;
    }

    protected function resolveEnemiesForNode(AdventureNode $node): Collection
    {
        $configuredEnemyId = data_get($node->metadata, 'enemy_id');
        $configuredCount = (int) data_get($node->metadata, 'enemy_count', 0);
        $maxEnemies = 5;
        $enemyCount = $node->node_type === 'boss'
            ? 1
            : max(1, min($maxEnemies, $configuredCount > 0 ? $configuredCount : random_int(1, $maxEnemies)));

        if ($configuredEnemyId) {
            $enemy = Enemy::query()->find($configuredEnemyId);

            if ($enemy) {
                return collect(range(1, $enemyCount))->map(fn (): Enemy => $enemy);
            }
        }

        $query = Enemy::query()
            ->where('learning_domain_id', $node->map?->world?->learning_domain_id);

        if ($node->node_type === 'boss') {
            $query->where('is_boss', true)->orderByDesc('level');
        } else {
            $query->where('is_boss', false)->orderBy('level');
        }

        $enemies = $query->get();

        if ($enemies->isEmpty()) {
            throw new InvalidArgumentException('Aucun ennemi n’est configuré pour cette zone.');
        }

        if ($node->node_type === 'boss') {
            return collect([$enemies->first()]);
        }

        $index = max(0, (($node->map?->sort_order ?? 1) * 10) + (($node->sort_order ?? 1) - 1)) % $enemies->count();
        $rotated = $enemies->values();

        return collect(range(0, $enemyCount - 1))
            ->map(function (int $offset) use ($rotated, $index): Enemy {
                $poolIndex = ($index + $offset) % max(1, $rotated->count());

                return $rotated->get($poolIndex);
            });
    }

    protected function learningProfile(User $user, AdventureNode $node): array
    {
        $categoryProgress = UserLearningItemProgress::query()
            ->where('user_id', $user->id)
            ->where('item_type', LearningProgressService::ITEM_CATEGORY)
            ->where('learning_category_id', $node->learning_category_id)
            ->first();

        $lessonProgress = $node->lesson_id
            ? UserLessonProgress::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $node->lesson_id)
                ->first()
            : null;

        $masteryScore = (int) ($categoryProgress->mastery_score ?? 0);
        $accuracy = (int) round(
            ($categoryProgress && (int) $categoryProgress->attempts > 0)
                ? (($categoryProgress->correct_attempts / $categoryProgress->attempts) * 100)
                : 0
        );

        return [
            'mastery_score' => $masteryScore,
            'accuracy_score' => $accuracy,
            'lesson_completed' => (bool) ($lessonProgress?->quiz_passed ?? false),
            'precision_bonus' => min(18, (int) floor($masteryScore / 8) + (int) floor($accuracy / 15)),
            'damage_bonus' => min(14, (int) floor($masteryScore / 10) + (($lessonProgress?->quiz_passed ?? false) ? 3 : 0)),
            'crit_bonus' => min(14, (int) floor($masteryScore / 12) + (($lessonProgress?->quiz_passed ?? false) ? 2 : 0)),
            'mana_bonus' => min(16, (int) floor($masteryScore / 7)),
        ];
    }

    protected function buildActors(
        User $user,
        Collection $companions,
        Collection $enemies,
        AdventureNode $node,
        array $learningProfile,
    ): array {
        $actors = [];
        $heroBuild = $this->buildService->heroCombatBuild($user);
        $companionBuilds = $companions
            ->mapWithKeys(fn (UserCompanion $companion): array => [
                (int) $companion->id => $this->buildService->companionCombatBuild($companion),
            ]);
        $synergyState = $this->buildService->synergyState($heroBuild, $companionBuilds->values());

        $actors[$this->heroKey($user)] = $this->buildHeroActor(
            $user,
            $node,
            $learningProfile,
            $heroBuild,
            $synergyState['effects']['hero'] ?? [],
            collect($synergyState['synergies'] ?? [])->pluck('label')->all(),
        );

        foreach ($companions->values() as $index => $companion) {
            $actors[$this->companionKey($companion)] = $this->buildCompanionActor(
                $companion,
                $node,
                $learningProfile,
                $index + 1,
                $companionBuilds->get((int) $companion->id, []),
                $synergyState['effects']['companion:'.$companion->id] ?? [],
                collect($synergyState['synergies'] ?? [])->pluck('label')->all(),
            );
        }

        $enemyCount = max(1, $enemies->count());
        foreach ($enemies->values() as $index => $enemy) {
            $actors[$this->enemyKey($enemy, $index)] = $this->buildEnemyActor($enemy, $node, $learningProfile, $index, $enemyCount);
        }

        return $actors;
    }

    protected function buildHeroActor(
        User $user,
        AdventureNode $node,
        array $learningProfile,
        array $heroBuild,
        array $synergyEffects = [],
        array $synergyLabels = [],
    ): array
    {
        $avatar = $user->loadMissing('avatar')->avatar;
        $masteryScore = $learningProfile['mastery_score'];
        $lessonCompleted = $learningProfile['lesson_completed'];
        $level = max(1, (int) $user->level);
        [$positionX, $positionY] = $this->playerFormationPosition(0);
        $skills = collect($heroBuild['active_skills'] ?? [])->values()->all();
        $buildStats = $heroBuild['stat_modifiers'] ?? [];
        $synergyBundle = $this->synergyModifierBundle($synergyEffects);

        $baseHp = (int) round((110 + (($level - 1) * 12) + (int) floor($masteryScore / 4)) * (($buildStats['hp_multiplier'] ?? 1.0) * ($synergyBundle['hp_multiplier'] ?? 1.0)));
        $baseMana = 40 + (($level - 1) * 4) + $learningProfile['mana_bonus'] + (int) ($buildStats['mana_bonus'] ?? 0) + (int) ($synergyBundle['mana_bonus'] ?? 0);
        $baseAttack = 18 + (($level - 1) * 3) + $learningProfile['damage_bonus'] + (int) ($buildStats['attack_bonus'] ?? 0) + (int) ($synergyBundle['attack_bonus'] ?? 0);
        $baseMagicAttack = 16 + (($level - 1) * 3) + (int) floor($learningProfile['mana_bonus'] / 2) + (int) ($buildStats['magic_attack_bonus'] ?? 0) + (int) ($synergyBundle['magic_attack_bonus'] ?? 0);
        $baseDefense = 12 + (($level - 1) * 2) + ($lessonCompleted ? 2 : 0) + (int) ($buildStats['defense_bonus'] ?? 0) + (int) ($synergyBundle['defense_bonus'] ?? 0);
        $baseResistance = 11 + (($level - 1) * 2) + (int) ($buildStats['resistance_bonus'] ?? 0) + (int) ($synergyBundle['resistance_bonus'] ?? 0);
        $baseSpeed = 13 + (int) floor($level / 2) + (int) floor($masteryScore / 25) + (int) ($buildStats['speed_bonus'] ?? 0) + (int) ($synergyBundle['speed_bonus'] ?? 0);
        $basePrecision = 78 + $learningProfile['precision_bonus'] + (int) ($buildStats['precision_bonus'] ?? 0) + (int) ($synergyBundle['precision_bonus'] ?? 0);
        $baseCrit = 6 + $learningProfile['crit_bonus'] + (int) ($buildStats['crit_bonus'] ?? 0) + (int) ($synergyBundle['crit_bonus'] ?? 0);

        return [
            'key' => $this->heroKey($user),
            'actor_type' => BattleActorType::Hero->value,
            'reference_id' => $user->id,
            'side' => 'player',
            'name' => $avatar?->nickname ?: $user->name,
            'role' => 'hero',
            'level' => $level,
            'current_hp' => $baseHp,
            'max_hp' => $baseHp,
            'current_mana' => $baseMana,
            'max_mana' => $baseMana,
            'attack' => $baseAttack,
            'magic_attack' => $baseMagicAttack,
            'defense' => $baseDefense,
            'resistance' => $baseResistance,
            'speed' => $baseSpeed,
            'precision' => $basePrecision,
            'crit_rate' => $baseCrit,
            'crit_damage' => 1.7,
            'cooldowns' => [],
            'effects' => array_values(array_filter([
                $lessonCompleted ? [
                    'key' => 'academy-link',
                    'label' => 'Lecon maitrisee',
                    'rounds_remaining' => 2,
                    'attack_bonus' => 2,
                    'precision_bonus' => 4,
                ] : null,
            ])),
            'skills' => $skills,
            'passive_skills' => $heroBuild['passive_skills'] ?? [],
            'build_modifiers' => array_values(array_merge(
                $heroBuild['conditional_modifiers'] ?? [],
                $synergyBundle['conditional_modifiers'] ?? [],
            )),
            'sprite_sheet' => null,
            'sprite' => $this->heroSpriteMetadata(),
            'frame_width' => 64,
            'frame_height' => 64,
            'direction' => 'right',
            'animation_state' => 'idle',
            'current_frame' => 0,
            'position_x' => $positionX,
            'position_y' => $positionY,
            'layer_order' => 1,
            'avatar' => [
                'body_key' => data_get($avatar?->equipped_items, 'body', config('avatar.defaults.body')),
                'hair_key' => data_get($avatar?->equipped_items, 'hair', config('avatar.defaults.hair')),
                'outfit_preset_key' => data_get($avatar?->equipped_items, 'outfit_preset', data_get($avatar?->equipped_items, 'outfit', $avatar?->style)),
                'equipped_items' => $avatar?->equipped_items ?? [],
                'skin' => data_get($avatar?->colors, 'skin', config('avatar.defaults.skin')),
                'hair_color' => data_get($avatar?->colors, 'hair', config('avatar.defaults.hair_color')),
                'accent' => data_get($avatar?->colors, 'accent', '#4C6FFF'),
            ],
            'bonuses' => [
                'mastery_score' => $masteryScore,
                'precision_bonus' => $learningProfile['precision_bonus'],
                'damage_bonus' => $learningProfile['damage_bonus'],
                'crit_bonus' => $learningProfile['crit_bonus'],
                'skill_tree' => array_values(array_unique($synergyLabels)),
            ],
            'build' => [
                'class_key' => $heroBuild['class_key'] ?? null,
                'class_label' => $heroBuild['class_label'] ?? null,
                'role_key' => $heroBuild['role_key'] ?? null,
                'role_label' => $heroBuild['role_label'] ?? null,
                'specialization_key' => $heroBuild['specialization_key'] ?? null,
                'specialization_label' => $heroBuild['specialization_label'] ?? null,
                'tags' => $heroBuild['actor_tags'] ?? [],
                'synergies' => $synergyLabels,
            ],
        ];
    }

    protected function buildCompanionActor(
        UserCompanion $userCompanion,
        AdventureNode $node,
        array $learningProfile,
        int $positionOffset,
        array $build = [],
        array $synergyEffects = [],
        array $synergyLabels = [],
    ): array {
        $specialized = $userCompanion->isSpecializedFor($node->learningCategory);
        [$positionX, $positionY] = $this->playerFormationPosition($positionOffset);
        $skillPool = collect($build['active_skills'] ?? [])->values();
        if ($skillPool->isEmpty()) {
            $skillPool = collect(config('combat.skills.fallback_companion_skills', []))
                ->map(fn (string $slug) => $this->skillBlueprint($slug));
        }
        $buildStats = $build['stat_modifiers'] ?? [];
        $synergyBundle = $this->synergyModifierBundle($synergyEffects);
        $baseHp = (int) round(($userCompanion->maxHp() + ($specialized ? 8 : 0)) * (($buildStats['hp_multiplier'] ?? 1.0) * ($synergyBundle['hp_multiplier'] ?? 1.0)));
        $baseMana = 28 + ($userCompanion->level * 3) + (int) ($buildStats['mana_bonus'] ?? 0) + (int) ($synergyBundle['mana_bonus'] ?? 0);
        $baseAttack = $userCompanion->attackStat() + (int) floor($learningProfile['mastery_score'] / 18) + ($specialized ? 4 : 0) + (int) ($buildStats['attack_bonus'] ?? 0) + (int) ($synergyBundle['attack_bonus'] ?? 0);
        $baseMagicAttack = 14 + ($userCompanion->level * 3) + (int) ($buildStats['magic_attack_bonus'] ?? 0) + (int) ($synergyBundle['magic_attack_bonus'] ?? 0);
        $baseDefense = $userCompanion->defenseStat() + ($specialized ? 2 : 0) + (int) ($buildStats['defense_bonus'] ?? 0) + (int) ($synergyBundle['defense_bonus'] ?? 0);
        $baseResistance = 11 + (int) floor($userCompanion->level * 1.8) + (int) ($buildStats['resistance_bonus'] ?? 0) + (int) ($synergyBundle['resistance_bonus'] ?? 0);
        $baseSpeed = $userCompanion->speedStat() + ($specialized ? 2 : 0) + (int) ($buildStats['speed_bonus'] ?? 0) + (int) ($synergyBundle['speed_bonus'] ?? 0);
        $basePrecision = 72 + (int) floor($learningProfile['mastery_score'] / 8) + ($specialized ? 8 : 0) + (int) ($buildStats['precision_bonus'] ?? 0) + (int) ($synergyBundle['precision_bonus'] ?? 0);
        $baseCrit = 4 + (int) floor($learningProfile['mastery_score'] / 20) + ($specialized ? 3 : 0) + (int) ($buildStats['crit_bonus'] ?? 0) + (int) ($synergyBundle['crit_bonus'] ?? 0);

        return [
            'key' => $this->companionKey($userCompanion),
            'actor_type' => BattleActorType::UserCompanion->value,
            'reference_id' => $userCompanion->id,
            'side' => 'player',
            'name' => $userCompanion->companion->name,
            'role' => $userCompanion->companion->role?->value ?? 'companion',
            'level' => (int) $userCompanion->level,
            'current_hp' => $baseHp,
            'max_hp' => $baseHp,
            'current_mana' => $baseMana,
            'max_mana' => $baseMana,
            'attack' => $baseAttack,
            'magic_attack' => $baseMagicAttack,
            'defense' => $baseDefense,
            'resistance' => $baseResistance,
            'speed' => $baseSpeed,
            'precision' => $basePrecision,
            'crit_rate' => $baseCrit,
            'crit_damage' => 1.55,
            'cooldowns' => [],
            'effects' => $specialized ? [[
                'key' => 'specialization-link',
                'label' => 'Affinite',
                'rounds_remaining' => 2,
                'attack_bonus' => 2,
                'precision_bonus' => 4,
            ]] : [],
            'skills' => $skillPool->values()->all(),
            'passive_skills' => $build['passive_skills'] ?? [],
            'build_modifiers' => array_values(array_merge(
                $build['conditional_modifiers'] ?? [],
                $synergyBundle['conditional_modifiers'] ?? [],
            )),
            'sprite_sheet' => $this->companionSpriteUrl($userCompanion, $positionOffset),
            'sprite' => $this->companionSpriteMetadata($userCompanion, $positionOffset),
            'frame_width' => 64,
            'frame_height' => 64,
            'direction' => 'right',
            'animation_state' => 'idle',
            'current_frame' => 0,
            'position_x' => $positionX,
            'position_y' => $positionY,
            'layer_order' => 1 + $positionOffset,
            'portrait' => $this->companionSpriteUrl($userCompanion, $positionOffset),
            'specialized' => $specialized,
            'build' => [
                'class_key' => $build['class_key'] ?? null,
                'class_label' => $build['class_label'] ?? null,
                'role_key' => $build['role_key'] ?? null,
                'role_label' => $build['role_label'] ?? null,
                'specialization_key' => $build['specialization_key'] ?? null,
                'specialization_label' => $build['specialization_label'] ?? null,
                'tags' => $build['actor_tags'] ?? [],
                'synergies' => $synergyLabels,
            ],
        ];
    }

    protected function buildEnemyActor(Enemy $enemy, AdventureNode $node, array $learningProfile, int $formationIndex = 0, int $formationCount = 1): array
    {
        $multiplier = match ($node->node_type) {
            'boss' => 1.65,
            'combat_elite' => 1.35,
            default => 1.0,
        };

        $attackMultiplier = match ($node->node_type) {
            'boss' => 1.28,
            'combat_elite' => 1.15,
            default => 1.0,
        };

        $defenseMultiplier = match ($node->node_type) {
            'boss' => 1.22,
            'combat_elite' => 1.12,
            default => 1.0,
        };

        $speedMultiplier = match ($node->node_type) {
            'boss' => 1.1,
            'combat_elite' => 1.06,
            default => 1.0,
        };

        $skills = collect($enemy->skill_set ?? [])
            ->map(function (array $skill) {
                $slug = match ($skill['type'] ?? 'attack') {
                    'buff' => 'enemy-rally',
                    'heavy' => 'enemy-crush',
                    default => 'enemy-strike',
                };

                return array_merge($this->skillBlueprint($slug), [
                    'power' => max((int) ($skill['power'] ?? 0), (int) data_get($this->skillBlueprint($slug), 'power', 0)),
                ]);
            });

        if ($skills->isEmpty()) {
            $skills = collect([$this->skillBlueprint('enemy-strike')]);
        }

        if (in_array($node->node_type, ['combat_elite', 'boss'], true)) {
            $skills->push($this->skillBlueprint('enemy-crush'));
        }

        if ($node->node_type === 'boss') {
            $skills->push($this->skillBlueprint('enemy-rally'));
        }

        [$positionX, $positionY] = $this->enemyFormationPosition($formationIndex);

        return [
            'key' => $this->enemyKey($enemy, $formationIndex),
            'actor_type' => BattleActorType::Enemy->value,
            'reference_id' => $enemy->id,
            'side' => 'enemy',
            'name' => $enemy->name,
            'role' => $enemy->is_boss ? 'boss' : 'enemy',
            'level' => (int) $enemy->level,
            'current_hp' => (int) round($enemy->hp * $multiplier),
            'max_hp' => (int) round($enemy->hp * $multiplier),
            'current_mana' => $node->node_type === 'boss' ? 30 : 18,
            'max_mana' => $node->node_type === 'boss' ? 30 : 18,
            'attack' => (int) round($enemy->attack * $attackMultiplier),
            'defense' => (int) round($enemy->defense * $defenseMultiplier),
            'speed' => (int) round($enemy->speed * $speedMultiplier),
            'precision' => match ($node->node_type) {
                'boss' => 84,
                'combat_elite' => 79,
                default => 74,
            },
            'crit_rate' => $node->node_type === 'boss' ? 9 : 4,
            'crit_damage' => 1.5,
            'cooldowns' => [],
            'effects' => [],
            'skills' => $skills->unique('slug')->values()->all(),
            'sprite_sheet' => $this->enemySpriteUrl($enemy),
            'sprite' => $this->enemySpriteMetadata($enemy),
            'frame_width' => 64,
            'frame_height' => 64,
            'direction' => 'left',
            'animation_state' => 'idle',
            'current_frame' => 0,
            'position_x' => $positionX,
            'position_y' => $positionY,
            'layer_order' => 10 + $formationIndex,
            'knowledge_trial_ready' => ($node->node_type === 'boss'),
            'academic_pressure' => max(0, 10 - (int) floor(($learningProfile['mastery_score'] ?? 0) / 12)),
            'formation_count' => $formationCount,
        ];
    }

    protected function rewardPreview(Collection $enemies, AdventureNode $node): array
    {
        $baseCoins = (int) $enemies->sum(fn (Enemy $enemy): int => (int) $enemy->reward_coins);
        $countScaling = max(1, $enemies->count());

        return [
            'coins' => $baseCoins + ($node->node_type === 'boss' ? 25 : (8 * $countScaling)),
            'items' => [],
            'special_skill_points' => $node->node_type === 'boss' ? 1 : 0,
        ];
    }

    protected function buildTurnQueue(array $actors): array
    {
        return collect($actors)
            ->filter(fn (array $actor): bool => ($actor['current_hp'] ?? 0) > 0)
            ->sortByDesc(fn (array $actor) => ($actor['speed'] * 100) + ($actor['side'] === 'player' ? 10 : 0))
            ->keys()
            ->values()
            ->all();
    }

    protected function skillBlueprint(string $slug): array
    {
        $blueprint = config('combat.skills.catalog.'.$slug);

        if (! is_array($blueprint)) {
            throw new InvalidArgumentException("La competence {$slug} n'est pas configuree.");
        }

        return array_merge(['slug' => $slug], $blueprint);
    }

    protected function skillAvailable(?array $actor, array $skill): bool
    {
        if (! $actor || ($actor['current_hp'] ?? 0) <= 0) {
            return false;
        }

        return (int) ($actor['current_mana'] ?? 0) >= (int) ($skill['mana_cost'] ?? 0)
            && (int) data_get($actor, 'cooldowns.'.$skill['slug'], 0) <= 0;
    }

    protected function resolveTargetKey(array $state, string $actorKey, array $skill, ?string $targetKey = null): string
    {
        $actor = $state['actors'][$actorKey];

        return match ($skill['target'] ?? 'enemy') {
            'self' => $actorKey,
            'ally' => $targetKey && ($state['actors'][$targetKey]['side'] ?? null) === $actor['side']
                ? $targetKey
                : $this->lowestHealthActorKey($state, $actor['side']),
            'all_enemies' => $this->firstLivingActorKey($state, $actor['side'] === 'player' ? 'enemy' : 'player'),
            default => $targetKey && ($state['actors'][$targetKey]['side'] ?? null) !== $actor['side']
                ? $targetKey
                : $this->firstLivingActorKey($state, $actor['side'] === 'player' ? 'enemy' : 'player'),
        };
    }

    protected function applySkill(Battle $battle, array &$state, string $actorKey, array $skill, string $targetKey): array
    {
        $actor = &$state['actors'][$actorKey];
        $target = &$state['actors'][$targetKey];

        $actor['current_mana'] = max(0, (int) $actor['current_mana'] - (int) ($skill['mana_cost'] ?? 0));

        if ((int) ($skill['cooldown'] ?? 0) > 0) {
            $actor['cooldowns'][$skill['slug']] = (int) $skill['cooldown'];
        }

        $result = [
            'actor_key' => $actorKey,
            'target_key' => $targetKey,
            'skill_slug' => $skill['slug'],
            'skill_label' => $skill['label'],
            'outcome' => $skill['type'],
            'damage' => 0,
            'healing' => 0,
            'critical' => false,
            'text' => '',
        ];

        switch ($skill['type']) {
            case 'attack':
                $damage = $this->calculateDamage($battle, $state, $actor, $target, $skill);
                $target['current_hp'] = max(0, (int) $target['current_hp'] - $damage['amount']);
                $result['damage'] = $damage['amount'];
                $result['critical'] = $damage['critical'];
                $result['text'] = "{$actor['name']} utilise {$skill['label']} et inflige {$damage['amount']} degats.";
                if ($damage['critical']) {
                    $result['text'] .= ' Coup critique.';
                }
                break;

            case 'area_attack':
                $targetSide = $actor['side'] === 'player' ? 'enemy' : 'player';
                $impacts = collect($state['actors'] ?? [])
                    ->filter(fn (array $candidate): bool => ($candidate['side'] ?? null) === $targetSide && ($candidate['current_hp'] ?? 0) > 0)
                    ->map(function (array $candidate, string $candidateKey) use ($battle, &$state, $actor, $skill): array {
                        $candidateRef = &$state['actors'][$candidateKey];
                        $damage = $this->calculateDamage($battle, $state, $actor, $candidateRef, $skill);
                        $candidateRef['current_hp'] = max(0, (int) $candidateRef['current_hp'] - $damage['amount']);
                        $candidateRef['animation_state'] = 'hit';

                        return [
                            'target_key' => $candidateKey,
                            'name' => $candidateRef['name'],
                            'damage' => $damage['amount'],
                        ];
                    })
                    ->values();

                $result['damage'] = (int) $impacts->sum('damage');
                $result['target_key'] = $impacts->first()['target_key'] ?? $targetKey;
                $result['text'] = "{$actor['name']} déchaîne {$skill['label']} et touche {$impacts->count()} cible(s).";
                break;

            case 'heal':
                $healBundle = $this->combatModifierBundle($actor, $skill);
                $healAmount = min(
                    (int) round(((int) ($skill['power'] ?? 0) + (int) floor((($actor['magic_attack'] ?? $actor['attack'] ?? 0)) / 5)) * ($healBundle['healing_multiplier'] ?? 1.0)),
                    max(0, (int) $target['max_hp'] - (int) $target['current_hp'])
                );
                $target['current_hp'] += $healAmount;
                $result['healing'] = $healAmount;
                $result['text'] = "{$actor['name']} restaure {$healAmount} PV a {$target['name']}.";
                break;

            case 'defense':
            case 'shield':
            case 'buff':
            case 'penalty_reduction':
            case 'theme_bonus':
                $skillEffect = [
                    'key' => $skill['slug'],
                    'label' => $skill['label'],
                    'rounds_remaining' => (int) ($skill['duration_rounds'] ?? 1),
                    'attack_bonus' => (int) ($skill['attack_bonus'] ?? 0),
                    'defense_bonus' => (int) ($skill['defense_bonus'] ?? 0),
                    'precision_bonus' => (int) ($skill['precision_bonus'] ?? 0),
                    'crit_bonus' => (int) ($skill['crit_bonus'] ?? 0),
                    'incoming_damage_multiplier' => $skill['incoming_damage_multiplier'] ?? null,
                    'penalty_reduction' => (int) ($skill['penalty_reduction'] ?? 0),
                ];

                if (($skill['type'] ?? null) === 'theme_bonus') {
                    $battleThemeSlug = $battle->adventureNode?->learningCategory?->slug;
                    $skillThemeSlug = $skill['theme_slug'] ?? null;
                    if ($battleThemeSlug && $skillThemeSlug && $battleThemeSlug === $skillThemeSlug) {
                        $skillEffect['attack_bonus'] += (int) ($skill['themed_attack_bonus'] ?? 0);
                        $skillEffect['precision_bonus'] += (int) ($skill['themed_precision_bonus'] ?? 0);
                    }
                }

                $this->applyEffect($target, [
                    ...$skillEffect,
                ]);
                $buildBundle = $this->combatModifierBundle($actor, $skill);
                if (($buildBundle['resource_on_shield'] ?? 0) > 0) {
                    $actor['current_mana'] = min((int) $actor['max_mana'], (int) $actor['current_mana'] + (int) $buildBundle['resource_on_shield']);
                }
                $result['text'] = "{$actor['name']} active {$skill['label']}.";
                break;

            case 'debuff':
                $this->applyEffect($target, [
                    'key' => $skill['slug'],
                    'label' => $skill['label'],
                    'rounds_remaining' => (int) ($skill['duration_rounds'] ?? 1),
                    'attack_bonus' => (int) ($skill['attack_bonus'] ?? 0),
                    'precision_bonus' => (int) ($skill['precision_bonus'] ?? 0),
                ]);
                $result['text'] = "{$actor['name']} fragilise {$target['name']} avec {$skill['label']}.";
                break;
        }

        $actor['animation_state'] = 'attack';
        $target['animation_state'] = $result['damage'] > 0 ? 'hit' : $target['animation_state'];

        if (($result['critical'] ?? false) && ($actor['build_modifiers'] ?? []) !== []) {
            $critBundle = $this->combatModifierBundle($actor, $skill);
            if (($critBundle['cooldown_refund_on_crit'] ?? 0) > 0) {
                foreach (($actor['cooldowns'] ?? []) as $cooldownKey => $cooldownValue) {
                    $actor['cooldowns'][$cooldownKey] = max(0, (int) $cooldownValue - (int) $critBundle['cooldown_refund_on_crit']);
                }
            }
        }

        if ($result['damage'] > 0) {
            foreach ($this->combatModifierBundle($actor, $skill)['statuses_on_hit'] ?? [] as $status) {
                $this->applyEffect($target, $status);
            }
        }

        $state['last_action'] = $result;
        // Maintain a per-turn replay log so the front-end can play every
        // action that ran since the player last hit submit (their own turn,
        // then any automatic enemy turns), instead of only the very last one.
        $state['turn_actions'] = array_merge($state['turn_actions'] ?? [], [$result]);
        $state['logs'] = $this->appendLog($state['logs'] ?? [], [
            'tone' => $actor['side'] === 'player' ? 'amber' : 'rose',
            'text' => $result['text'],
        ]);

        $this->handleBossPhases($state, $battle);

        return $result;
    }

    protected function calculateDamage(Battle $battle, array $state, array $actor, array $target, array $skill): array
    {
        $seed = crc32(implode('|', [
            $battle->id,
            $state['round'] ?? 1,
            $state['current_turn_index'] ?? 0,
            $actor['key'],
            $skill['slug'],
            $target['key'],
        ]));

        $actorEffects = $this->effectTotals($actor);
        $targetEffects = $this->effectTotals($target);
        $actorBuild = $this->combatModifierBundle($actor, $skill);
        $targetBuild = $this->combatModifierBundle($target, $skill);
        $usesMagic = ($skill['damage_family'] ?? null) === 'magical';
        $offenseStat = $usesMagic ? (int) ($actor['magic_attack'] ?? $actor['attack'] ?? 0) : (int) ($actor['attack'] ?? 0);
        $defenseStat = $usesMagic ? (int) ($target['resistance'] ?? $target['defense'] ?? 0) : (int) ($target['defense'] ?? 0);
        $base = max(
            4,
            ($offenseStat + (int) ($skill['power'] ?? 0) + (int) ($actorEffects['attack_bonus'] ?? 0) + (int) ($actorBuild['attack_bonus'] ?? 0))
                - ($defenseStat + (int) ($targetEffects['defense_bonus'] ?? 0) + (int) ($targetBuild['defense_bonus'] ?? 0))
        );
        $precisionFactor = 1 + ((((int) $actor['precision'] + (int) ($actorEffects['precision_bonus'] ?? 0) + (int) ($actorBuild['precision_bonus'] ?? 0)) - 75) / 220);
        $variation = 0.92 + (($seed % 14) / 100);
        $critRate = (int) $actor['crit_rate'] + (int) ($actorEffects['crit_bonus'] ?? 0) + (int) ($skill['crit_bonus'] ?? 0) + (int) ($actorBuild['crit_bonus'] ?? 0);
        $critical = ($seed % 100) < max(0, $critRate);
        $incomingMultiplier = ($targetEffects['incoming_damage_multiplier'] ?? 1.0) * ($targetBuild['incoming_damage_multiplier'] ?? 1.0);
        $outgoingMultiplier = $actorBuild['damage_multiplier'] ?? 1.0;
        $amount = (int) round($base * $precisionFactor * $variation * $incomingMultiplier * $outgoingMultiplier);

        if ($critical) {
            $amount = (int) round($amount * (float) ($actor['crit_damage'] ?? 1.5));
        }

        return [
            'amount' => max(1, $amount),
            'critical' => $critical,
        ];
    }

    protected function applyEffect(array &$actor, array $effect): void
    {
        $effects = collect($actor['effects'] ?? [])
            ->reject(fn (array $current): bool => ($current['key'] ?? null) === $effect['key'])
            ->values()
            ->all();

        $effects[] = array_filter($effect, fn ($value) => $value !== null);
        $actor['effects'] = $effects;
    }

    protected function effectTotals(array $actor): array
    {
        $totals = [
            'attack_bonus' => 0,
            'defense_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'incoming_damage_multiplier' => 1.0,
        ];

        foreach ($actor['effects'] ?? [] as $effect) {
            $totals['attack_bonus'] += (int) ($effect['attack_bonus'] ?? 0);
            $totals['defense_bonus'] += (int) ($effect['defense_bonus'] ?? 0);
            $totals['precision_bonus'] += (int) ($effect['precision_bonus'] ?? 0);
            $totals['crit_bonus'] += (int) ($effect['crit_bonus'] ?? 0);
            if (isset($effect['incoming_damage_multiplier'])) {
                $totals['incoming_damage_multiplier'] *= (float) $effect['incoming_damage_multiplier'];
            }
        }

        return $totals;
    }

    protected function synergyModifierBundle(array $effects): array
    {
        $bundle = [
            'hp_multiplier' => 1.0,
            'mana_bonus' => 0,
            'attack_bonus' => 0,
            'magic_attack_bonus' => 0,
            'defense_bonus' => 0,
            'resistance_bonus' => 0,
            'speed_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'conditional_modifiers' => [],
        ];

        foreach ($effects as $effect) {
            if (! isset($effect['match_skill_tags'])) {
                $stat = $effect['stat'] ?? null;
                $mode = $effect['mode'] ?? 'add';
                $value = $effect['value'] ?? null;
                if ($stat && $value !== null) {
                    if ($mode === 'multiply') {
                        $bundle[$stat] = ($bundle[$stat] ?? 1.0) * (float) $value;
                    } else {
                        $bundle[$stat] = ($bundle[$stat] ?? 0) + (int) $value;
                    }
                }
                continue;
            }

            $bundle['conditional_modifiers'][] = $effect;
        }

        return $bundle;
    }

    protected function combatModifierBundle(array $actor, array $skill): array
    {
        $bundle = [
            'attack_bonus' => 0,
            'defense_bonus' => 0,
            'precision_bonus' => 0,
            'crit_bonus' => 0,
            'damage_multiplier' => 1.0,
            'incoming_damage_multiplier' => 1.0,
            'healing_multiplier' => 1.0,
            'cooldown_refund_on_crit' => 0,
            'resource_on_shield' => 0,
            'statuses_on_hit' => [],
        ];

        foreach (($actor['build_modifiers'] ?? []) as $modifier) {
            if (! $this->modifierMatchesSkill($modifier, $skill)) {
                continue;
            }

            $stat = $modifier['stat'] ?? null;
            if ($stat === null) {
                continue;
            }

            if ($stat === 'apply_status' && isset($modifier['status']) && is_array($modifier['status'])) {
                $bundle['statuses_on_hit'][] = $modifier['status'];
                continue;
            }

            $value = $modifier['value'] ?? null;
            $mode = $modifier['mode'] ?? 'add';
            if ($value === null) {
                continue;
            }

            if (in_array($stat, ['damage_multiplier', 'incoming_damage_multiplier', 'healing_multiplier'], true)) {
                $bundle[$stat] *= ($mode === 'multiply') ? (float) $value : (1 + (float) $value);
                continue;
            }

            $bundle[$stat] = ($bundle[$stat] ?? 0) + (int) $value;
        }

        return $bundle;
    }

    protected function modifierMatchesSkill(array $modifier, array $skill): bool
    {
        $requiredTags = collect($modifier['match_skill_tags'] ?? [])->filter()->values();
        if ($requiredTags->isEmpty()) {
            return true;
        }

        $skillTags = collect($skill['tags'] ?? [])->filter()->values();

        return $requiredTags->every(fn (string $tag): bool => $skillTags->contains($tag));
    }

    protected function resolveSpriteSheetMetadata(
        string $url,
        string $absolutePath,
        int $frameWidth,
        int $frameHeight,
    ): array {
        $columns = 3;
        $rows = 4;

        if (is_file($absolutePath)) {
            $dimensions = @getimagesize($absolutePath);

            if (is_array($dimensions) && ($dimensions[0] ?? 0) > 0 && ($dimensions[1] ?? 0) > 0) {
                $columns = max(1, (int) round($dimensions[0] / max(1, $frameWidth)));
                $rows = max(1, (int) round($dimensions[1] / max(1, $frameHeight)));
            }
        }

        return [
            'url' => $url,
            'frame_width' => $frameWidth,
            'frame_height' => $frameHeight,
            'columns' => $columns,
            'rows' => $rows,
            'left_view_row' => $this->resolveLeftFacingRow($rows),
            'idle_col' => $this->resolveIdleColumn($rows),
        ];
    }

    protected function resolveLeftFacingRow(int $rows): int
    {
        $simpleFallbackRow = (int) config('combat.sprites.simple_left_direction_row', 1);

        return LpcSprite::combatIdleRowForSheet($rows, $simpleFallbackRow);
    }

    protected function resolveIdleColumn(int $rows): int
    {
        return LpcSprite::idleColumnForSheet($rows);
    }

    protected function companionSpriteUrl(UserCompanion $userCompanion, int $positionOffset): ?string
    {
        if ($userCompanion->companion->sprite_path) {
            return str_starts_with($userCompanion->companion->sprite_path, 'companions/')
                ? '/images/'.$userCompanion->companion->sprite_path
                : '/storage/'.$userCompanion->companion->sprite_path;
        }

        $fallbacks = array_values(config('combat.defaults.companion_sprites', []));
        $fallbackPath = $fallbacks[max(0, ($positionOffset - 1) % max(1, count($fallbacks)))] ?? null;

        return $fallbackPath ? '/'.$fallbackPath : null;
    }

    protected function companionSpriteMetadata(UserCompanion $userCompanion, int $positionOffset): ?array
    {
        if ($userCompanion->companion->sprite_path) {
            $frameSize = str_starts_with($userCompanion->companion->sprite_path, 'companions/') ? 64 : 96;
            $absolutePath = str_starts_with($userCompanion->companion->sprite_path, 'companions/')
                ? public_path('images/'.$userCompanion->companion->sprite_path)
                : storage_path('app/public/'.$userCompanion->companion->sprite_path);
            $url = str_starts_with($userCompanion->companion->sprite_path, 'companions/')
                ? '/images/'.$userCompanion->companion->sprite_path
                : '/storage/'.$userCompanion->companion->sprite_path;

            $metadata = $this->resolveSpriteSheetMetadata(
                $url,
                $absolutePath,
                $frameSize,
                $frameSize,
            );

            $metadata['animation_profile'] = 'lpc';
            $metadata['mirror_right'] = false;

            return $metadata;
        }

        $fallbacks = array_values(config('combat.defaults.companion_sprites', []));
        $fallbackPath = $fallbacks[max(0, ($positionOffset - 1) % max(1, count($fallbacks)))] ?? null;
        if (! $fallbackPath) {
            return null;
        }

        $metadata = $this->resolveSpriteSheetMetadata(
            '/'.$fallbackPath,
            public_path($fallbackPath),
            64,
            64,
        );

        $metadata['animation_profile'] = 'lpc';
        $metadata['mirror_right'] = false;

        return $metadata;
    }

    protected function enemySpriteUrl(Enemy $enemy): ?string
    {
        $path = $enemy->image ?: config('combat.defaults.enemy_sprite');
        if (! $path) {
            return null;
        }

        // Craftpix monsters reference a directory (or any file inside it):
        // resolve to the default-state PNG so the legacy <img> fallback
        // still has something concrete to point at.
        $craftpix = CraftpixSprite::resolve($path);
        if ($craftpix !== null) {
            return $craftpix['states'][$craftpix['default_state']]['url'] ?? null;
        }

        return '/'.$path;
    }

    protected function enemySpriteMetadata(Enemy $enemy): ?array
    {
        $path = $enemy->image ?: config('combat.defaults.enemy_sprite');
        if (! $path) {
            return null;
        }

        // Craftpix-style monsters (Demon_X / Skeleton_X) ship one PNG per
        // animation state instead of a single multi-direction sheet, so they
        // need a completely different metadata payload. The renderer picks
        // up `animation_profile === 'craftpix'` and toggles between the
        // per-state images at runtime.
        $craftpix = CraftpixSprite::resolve($path);
        if ($craftpix !== null) {
            $defaultState = $craftpix['states'][$craftpix['default_state']] ?? null;

            return [
                'animation_profile' => 'craftpix',
                'craftpix' => $craftpix,
                'frame_width' => $craftpix['frame_width'],
                'frame_height' => $craftpix['frame_height'],
                // The static fields below describe the default (idle) strip
                // so the rest of the rendering pipeline – which expects an
                // LPC-style url/cols/rows triplet – still gets a sensible
                // fallback if it can't read the craftpix payload.
                'url' => $defaultState['url'] ?? null,
                'columns' => (int) ($defaultState['cols'] ?? 1),
                'rows' => (int) ($defaultState['rows'] ?? 1),
                'left_view_row' => 0,
                'idle_col' => 0,
            ];
        }

        $isImp     = str_contains($path, 'images/monsters/imp/');
        $frameSize = $isImp ? 64 : 112;

        $meta = $this->resolveSpriteSheetMetadata(
            '/'.$path,
            public_path($path),
            $frameSize,
            $frameSize,
        );

        // The imp sheet uses row 3 for the left-facing side profile that should
        // look toward the party when enemies are staged on the right.
        if ($isImp) {
            $meta['left_view_row'] = 3;
        }

        return $meta;
    }

    protected function heroSpriteMetadata(): array
    {
        return [
            'columns' => LpcSprite::UNIVERSAL_COLUMN_COUNT,
            'rows' => LpcSprite::EXTENDED_ROW_COUNT,
            'left_view_row' => LpcSprite::combatIdleRowForSheet(LpcSprite::EXTENDED_ROW_COUNT),
            'idle_col' => LpcSprite::combatReadyColumn(),
            'animation_profile' => 'lpc',
            'mirror_right' => false,
        ];
    }

    protected function refreshActorVisuals(array $actor): array
    {
        if (($actor['actor_type'] ?? null) === BattleActorType::Hero->value) {
            [$positionX, $positionY] = $this->playerFormationPosition(0);
            $actor['sprite'] = $this->heroSpriteMetadata();
            $actor['frame_width'] = 64;
            $actor['frame_height'] = 64;
            $actor['position_x'] = $positionX;
            $actor['position_y'] = $positionY;
        }

        if (($actor['actor_type'] ?? null) === BattleActorType::UserCompanion->value) {
            $positionOffset = max(1, (int) (($actor['layer_order'] ?? 2) - 1));
            [$positionX, $positionY] = $this->playerFormationPosition($positionOffset);
            $userCompanion = UserCompanion::query()->with('companion')->find($actor['reference_id'] ?? null);
            if ($userCompanion) {
                $actor['sprite_sheet'] = $this->companionSpriteUrl($userCompanion, $positionOffset);
                $actor['sprite'] = $this->companionSpriteMetadata($userCompanion, $positionOffset);
                $actor['frame_width'] = 64;
                $actor['frame_height'] = 64;
            }
            $actor['position_x'] = $positionX;
            $actor['position_y'] = $positionY;
        }

        if (($actor['actor_type'] ?? null) === BattleActorType::Enemy->value) {
            $enemy = Enemy::query()->find($actor['reference_id'] ?? null);
            if ($enemy) {
                $actor['sprite_sheet'] = $this->enemySpriteUrl($enemy);
                $actor['sprite'] = $this->enemySpriteMetadata($enemy);
                $actor['frame_width'] = 64;
                $actor['frame_height'] = 64;
            }

            // Re-align enemies to the current formation so battles already in
            // progress pick up layout tweaks without needing a fresh fight.
            $formationIndex = max(0, (int) (($actor['layer_order'] ?? 10) - 10));
            [$positionX, $positionY] = $this->enemyFormationPosition($formationIndex);
            $actor['position_x'] = $positionX;
            $actor['position_y'] = $positionY;
        }

        return $actor;
    }

    /**
     * Enemy formation parallel to the hero diagonal: same direction (back-row
     * upper-left to front-row lower-right), shifted to the right side of the
     * stage. Slots 3-4 cover larger encounters.
     */
    protected function enemyFormationPosition(int $slot): array
    {
        $positions = [
            [66, 44],
            [71, 57],
            [76, 70],
            [63, 49],
            [68, 62],
        ];

        return $positions[max(0, min(count($positions) - 1, $slot))];
    }

    protected function playerFormationPosition(int $slot): array
    {
        $positions = [
            [24, 44],
            [29, 57],
            [34, 70],
        ];

        return $positions[max(0, min(count($positions) - 1, $slot))];
    }

    protected function appendLog(array $logs, array $entry): array
    {
        $logs[] = $entry;

        return collect($logs)->take(-8)->values()->all();
    }

    protected function handleBossPhases(array &$state, Battle $battle): void
    {
        $enemyKey = $this->firstLivingActorKey($state, 'enemy');
        if (! $enemyKey) {
            return;
        }
        $enemy = &$state['actors'][$enemyKey];

        if (! $enemy || ($enemy['role'] ?? null) !== 'boss') {
            return;
        }

        $phaseIndex = (int) ($state['phase_index'] ?? 0);
        $phases = config('combat.boss_phases', []);
        $phase = $phases[$phaseIndex] ?? null;

        if (! $phase) {
            return;
        }

        $ratio = ((int) $enemy['current_hp']) / max(1, (int) $enemy['max_hp']);

        if ($ratio > (float) $phase['threshold']) {
            return;
        }

        $effect = array_merge([
            'key' => 'boss-phase-'.$phaseIndex,
            'label' => $phase['label'],
        ], $phase['effect']);
        $this->applyEffect($enemy, $effect);

        $state['phase_index'] = $phaseIndex + 1;
        $state['boss_phase'] = [
            'label' => $phase['label'],
            'message' => $phase['message'],
        ];
        $state['logs'] = $this->appendLog($state['logs'] ?? [], [
            'tone' => 'rose',
            'text' => $phase['message'],
        ]);

        if ($phaseIndex === 1) {
            $masteryScore = (int) data_get($battle->metadata, 'academic.mastery_score', 0);
            $lessonCompleted = (bool) data_get($battle->metadata, 'academic.lesson_completed', false);

            if ($masteryScore >= 60 || $lessonCompleted) {
                foreach ($state['actors'] as &$actor) {
                    if (($actor['side'] ?? null) !== 'player' || ($actor['current_hp'] ?? 0) <= 0) {
                        continue;
                    }

                    $this->applyEffect($actor, [
                        'key' => 'knowledge-window',
                        'label' => 'Fenetre tactique',
                        'rounds_remaining' => 2,
                        'attack_bonus' => 3,
                        'crit_bonus' => 8,
                    ]);
                }
                unset($actor);

                $state['logs'] = $this->appendLog($state['logs'] ?? [], [
                    'tone' => 'emerald',
                    'text' => 'Votre maîtrise du sujet révèle une faille dans la phase du boss.',
                ]);
            }
        }
    }

    protected function battleEnded(array $state): bool
    {
        return $this->firstLivingActorKey($state, 'player') === null
            || $this->firstLivingActorKey($state, 'enemy') === null;
    }

    protected function finalizeBattle(Battle $battle, array $state): Battle
    {
        $playerWon = $this->firstLivingActorKey($state, 'player') !== null
            && $this->firstLivingActorKey($state, 'enemy') === null;

        $battle->forceFill([
            'state' => $state,
            'status' => $playerWon ? BattleStatus::Won : BattleStatus::Lost,
            'ended_at' => now(),
        ])->save();

        if (! $playerWon || $battle->reward_claimed) {
            return $battle->refresh();
        }

        $rewards = $battle->metadata['rewards'] ?? [];
        $battle->loadMissing(['user', 'adventureNode']);

        $this->rewardService->grantCoins($battle->user, (int) ($rewards['coins'] ?? 0));
        $this->rewardService->grantSpecialSkillPoints($battle->user, (int) ($rewards['special_skill_points'] ?? 0));

        if ($battle->adventureNode) {
            $this->adventureMapService->markMissionBattleCompleted($battle->user, $battle->adventureNode, $battle);
        }

        $battle->forceFill([
            'reward_claimed' => true,
            'metadata' => array_merge($battle->metadata ?? [], [
                'completed_rewards' => $rewards,
            ]),
        ])->save();

        return $battle->refresh();
    }

    protected function advanceTurn(array &$state): void
    {
        $queue = collect($state['turn_queue'] ?? [])
            ->filter(fn (string $key): bool => ($state['actors'][$key]['current_hp'] ?? 0) > 0)
            ->values()
            ->all();

        if ($queue === []) {
            $queue = $this->buildTurnQueue($state['actors'] ?? []);
            $state['turn_queue'] = $queue;
            $state['current_turn_index'] = 0;
            $state['current_actor_key'] = $queue[0] ?? null;
            return;
        }

        $nextIndex = ((int) ($state['current_turn_index'] ?? 0)) + 1;

        if ($nextIndex >= count($queue)) {
            $state['round'] = ((int) ($state['round'] ?? 1)) + 1;
            $state['actors'] = $this->advanceRoundState($state['actors'] ?? []);
            $queue = $this->buildTurnQueue($state['actors'] ?? []);
            $nextIndex = 0;
        }

        $state['turn_queue'] = $queue;
        $state['current_turn_index'] = $nextIndex;
        $state['current_actor_key'] = $queue[$nextIndex] ?? null;
    }

    protected function advanceRoundState(array $actors): array
    {
        foreach ($actors as &$actor) {
            $actor['cooldowns'] = collect($actor['cooldowns'] ?? [])
                ->map(fn ($value) => max(0, (int) $value - 1))
                ->all();

            $damageOverTime = 0;
            $actor['effects'] = collect($actor['effects'] ?? [])
                ->map(function (array $effect) {
                    $effect['rounds_remaining'] = max(0, (int) ($effect['rounds_remaining'] ?? 1) - 1);

                    return $effect;
                })
                ->tap(function (Collection $effects) use (&$damageOverTime): void {
                    $damageOverTime = (int) $effects->sum(fn (array $effect): int => (int) ($effect['damage_over_time'] ?? 0));
                })
                ->filter(fn (array $effect): bool => (int) ($effect['rounds_remaining'] ?? 0) > 0)
                ->values()
                ->all();

            $actor['current_mana'] = min((int) $actor['max_mana'], (int) $actor['current_mana'] + 4);
            if ($damageOverTime > 0) {
                $actor['current_hp'] = max(0, (int) $actor['current_hp'] - $damageOverTime);
            }
            $actor['animation_state'] = ($actor['current_hp'] ?? 0) > 0 ? 'idle' : 'defeat';
        }
        unset($actor);

        return $actors;
    }

    protected function resolveEnemyDecision(array $state, string $actorKey, array $actor): array
    {
        $skills = collect($actor['skills'] ?? [])->filter(fn (array $skill): bool => $this->skillAvailable($actor, $skill))->values();

        if ($skills->isEmpty()) {
            $skills = collect([$this->skillBlueprint('enemy-strike')]);
        }

        $lowHp = ((int) $actor['current_hp'] / max(1, (int) $actor['max_hp'])) <= 0.45;
        $skill = $skills->first(function (array $skill) use ($lowHp, $actor) {
            if ($lowHp && $skill['type'] === 'buff' && empty($actor['effects'])) {
                return true;
            }

            return $skill['type'] === 'attack';
        }) ?? $skills->first();

        return [
            'skill' => $skill,
            'target_key' => $this->resolveTargetKey($state, $actorKey, $skill, null),
        ];
    }

    protected function recordTurn(Battle $battle, array $actor, string $skillSlug, array $result): void
    {
        $skillId = Skill::query()->where('slug', $skillSlug)->value('id');

        $battle->turns()->create([
            'turn_number' => ((int) $battle->turns()->max('turn_number')) + 1,
            'actor_type' => BattleActorType::from($actor['actor_type']),
            'actor_id' => (int) $actor['reference_id'],
            'skill_id' => $skillId,
            'damage' => (int) ($result['damage'] ?? 0),
            'healing' => (int) ($result['healing'] ?? 0),
            'result' => $result,
        ]);
    }

    protected function firstLivingActorKey(array $state, string $side): ?string
    {
        return collect($state['actors'] ?? [])
            ->filter(fn (array $actor, string $key): bool => ($actor['side'] ?? null) === $side && ($actor['current_hp'] ?? 0) > 0)
            ->keys()
            ->first();
    }

    protected function lowestHealthActorKey(array $state, string $side): ?string
    {
        return collect($state['actors'] ?? [])
            ->filter(fn (array $actor): bool => ($actor['side'] ?? null) === $side && ($actor['current_hp'] ?? 0) > 0)
            ->sortBy(fn (array $actor) => ($actor['current_hp'] / max(1, $actor['max_hp'])) * 100)
            ->keys()
            ->first();
    }

    protected function heroKey(User $user): string
    {
        return 'hero:'.$user->id;
    }

    protected function companionKey(UserCompanion $userCompanion): string
    {
        return 'companion:'.$userCompanion->id;
    }

    protected function enemyKey(Enemy $enemy, int $slot = 0): string
    {
        return 'enemy:'.$enemy->id.':'.$slot;
    }

    protected function startLogText(AdventureNode $node, Collection $enemies): string
    {
        $enemyNames = $enemies
            ->map(fn (Enemy $enemy): string => $enemy->name)
            ->values();

        if ($enemyNames->count() <= 1) {
            return "{$node->title} commence. {$enemyNames->first()} bloque la route.";
        }

        $preview = $enemyNames->take(3)->implode(', ');
        $suffix = $enemyNames->count() > 3 ? '...' : '';

        return "{$node->title} commence. Des ennemis apparaissent: {$preview}{$suffix}";
    }
}
