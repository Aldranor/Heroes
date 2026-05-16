<?php

namespace App\Services;

use App\Enums\BattleActorType;
use App\Enums\BattleStatus;
use App\Enums\LevelProgressStatus;
use App\Enums\SkillType;
use App\Models\Battle\Battle;
use App\Models\Battle\BattleTurn;
use App\Models\Creatures\Skill;
use App\Models\Creatures\UserCreature;
use App\Models\World\Enemy;
use App\Models\World\Level;
use App\Models\World\UserLevelProgress;
use App\Models\World\Zone;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BattleService
{
    public function __construct(
        protected RewardService $rewardService,
    ) {}

    public function startBattle(
        User $user,
        Enemy $enemy,
        array|Collection $team,
        ?Level $level = null,
    ): Battle {
        $userCreatures = $this->normalizeTeam($user, $team);

        $party = $userCreatures->pluck('id')->values()->all();
        $state = $this->buildInitialState($userCreatures, $enemy);

        return Battle::query()->create([
            'user_id' => $user->id,
            'enemy_id' => $enemy->id,
            'level_id' => $level?->id,
            'status' => BattleStatus::Ongoing,
            'party' => $party,
            'state' => $state,
            'started_at' => now(),
        ]);
    }

    public function playerAction(Battle $battle, UserCreature $userCreature, Skill $skill): Battle
    {
        return DB::transaction(function () use ($battle, $userCreature, $skill) {
            $lockedBattle = Battle::query()
                ->with(['enemy', 'level', 'user'])
                ->lockForUpdate()
                ->findOrFail($battle->id);

            if ($lockedBattle->status !== BattleStatus::Ongoing) {
                throw new InvalidArgumentException('Only ongoing battles can receive actions.');
            }

            $team = $this->loadBattleTeam($lockedBattle);
            $activeCreature = $team->get($userCreature->id);

            if (! $activeCreature) {
                throw new InvalidArgumentException('The selected creature is not part of this battle.');
            }

            $state = $lockedBattle->state ?? [];
            $playerKey = (string) $activeCreature->id;

            if (($state['player_team'][$playerKey]['current_hp'] ?? 0) <= 0) {
                throw new InvalidArgumentException('A knocked-out creature cannot act.');
            }

            $state['active_user_creature_id'] = $activeCreature->id;
            [$damage, $healing, $result] = $this->applyPlayerSkill($lockedBattle, $state, $activeCreature, $skill);

            $this->recordTurn(
                $lockedBattle,
                BattleActorType::UserCreature,
                $activeCreature->id,
                $skill,
                $damage,
                $healing,
                $result,
            );

            if ($state['enemy']['current_hp'] <= 0) {
                return $this->finalizeBattle($lockedBattle, $state, BattleStatus::Won);
            }

            return $this->runEnemyTurn($lockedBattle, $state, $team);
        });
    }

    public function enemyAction(Battle $battle): Battle
    {
        return DB::transaction(function () use ($battle) {
            $lockedBattle = Battle::query()
                ->with(['enemy', 'level', 'user'])
                ->lockForUpdate()
                ->findOrFail($battle->id);

            if ($lockedBattle->status !== BattleStatus::Ongoing) {
                throw new InvalidArgumentException('Only ongoing battles can receive actions.');
            }

            return $this->runEnemyTurn($lockedBattle, $lockedBattle->state ?? [], $this->loadBattleTeam($lockedBattle));
        });
    }

    public function calculateDamage(
        int $attackerAttack,
        int $skillPower,
        int $defenderDefense,
        float $modifier = 1.0,
    ): int {
        return max(1, (int) round((($attackerAttack + $skillPower) - $defenderDefense) * $modifier));
    }

    public function grantRewards(Battle $battle): Battle
    {
        $battle->loadMissing(['enemy', 'level', 'user']);

        if ($battle->reward_claimed || $battle->status !== BattleStatus::Won) {
            return $battle;
        }

        $coinReward = $battle->enemy->reward_coins;

        if ($battle->level) {
            $coinReward += $battle->level->reward_coins;
        }

        $this->rewardService->grantCoins($battle->user, $coinReward);

        if ($battle->level) {
            $this->markLevelAsCompleted($battle);
        }

        $battle->forceFill([
            'reward_claimed' => true,
        ])->save();

        return $battle->refresh();
    }

    protected function normalizeTeam(User $user, array|Collection $team): Collection
    {
        $ids = collect($team)->map(fn ($member) => $member instanceof UserCreature ? $member->id : $member)->all();

        $userCreatures = UserCreature::query()
            ->with('creature')
            ->where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($userCreatures->count() !== count($ids) || count($ids) < 1 || count($ids) > 3) {
            throw new InvalidArgumentException('A battle requires between one and three valid player creatures.');
        }

        return collect($ids)->map(fn ($id) => $userCreatures->get($id));
    }

    protected function buildInitialState(Collection $team, Enemy $enemy): array
    {
        $playerTeam = [];

        foreach ($team as $userCreature) {
            $playerTeam[(string) $userCreature->id] = [
                'current_hp' => $userCreature->current_hp ?: $userCreature->maxHp(),
                'max_hp' => $userCreature->maxHp(),
                'effects' => [],
            ];
        }

        return [
            'turn' => 1,
            'active_user_creature_id' => $team->first()->id,
            'player_team' => $playerTeam,
            'enemy' => [
                'current_hp' => $enemy->hp,
                'max_hp' => $enemy->hp,
                'effects' => [],
            ],
        ];
    }

    protected function loadBattleTeam(Battle $battle): Collection
    {
        return UserCreature::query()
            ->with('creature')
            ->whereIn('id', $battle->party ?? [])
            ->get()
            ->keyBy('id');
    }

    protected function applyPlayerSkill(Battle $battle, array &$state, UserCreature $userCreature, Skill $skill): array
    {
        $playerState = &$state['player_team'][(string) $userCreature->id];
        $enemyState = &$state['enemy'];
        $damage = 0;
        $healing = 0;
        $result = ['skill' => $skill->slug];

        switch ($skill->type) {
            case SkillType::Attack:
                $focusBonus = $playerState['effects']['focus'] ?? 0;
                unset($playerState['effects']['focus']);

                $damage = $this->calculateDamage(
                    $userCreature->attackStat(),
                    $skill->power + $focusBonus,
                    $battle->enemy->defense,
                );

                if (isset($enemyState['effects']['shield'])) {
                    $damage = max(1, (int) round($damage * (1 - $enemyState['effects']['shield'])));
                    unset($enemyState['effects']['shield']);
                }

                $enemyState['current_hp'] = max(0, $enemyState['current_hp'] - $damage);
                $result['effect'] = 'damage';
                break;

            case SkillType::Heal:
                $healing = min($skill->power, $playerState['max_hp'] - $playerState['current_hp']);
                $playerState['current_hp'] += $healing;
                $result['effect'] = 'heal';
                break;

            case SkillType::Defense:
                $playerState['effects']['shield'] = 0.5;
                $result['effect'] = 'shield';
                break;

            case SkillType::Buff:
                $playerState['effects']['focus'] = ($playerState['effects']['focus'] ?? 0) + $skill->power;
                $result['effect'] = 'focus';
                break;

            case SkillType::Debuff:
                $enemyState['effects']['weaken'] = ($enemyState['effects']['weaken'] ?? 0) + $skill->power;
                $result['effect'] = 'weaken';
                break;
        }

        return [$damage, $healing, $result];
    }

    protected function runEnemyTurn(Battle $battle, array $state, Collection $team): Battle
    {
        $targetId = $this->resolveTargetCreatureId($state);

        if (! $targetId) {
            return $this->finalizeBattle($battle, $state, BattleStatus::Lost);
        }

        $target = $team->get($targetId);
        $targetState = &$state['player_team'][(string) $targetId];
        $enemyAttack = max(1, $battle->enemy->attack - ($state['enemy']['effects']['weaken'] ?? 0));
        unset($state['enemy']['effects']['weaken']);

        $damage = $this->calculateDamage(
            $enemyAttack,
            $this->resolveEnemySkillPower($battle->enemy),
            $target->defenseStat(),
        );

        if (isset($targetState['effects']['shield'])) {
            $damage = max(1, (int) round($damage * (1 - $targetState['effects']['shield'])));
            unset($targetState['effects']['shield']);
        }

        $targetState['current_hp'] = max(0, $targetState['current_hp'] - $damage);
        $state['active_user_creature_id'] = $targetState['current_hp'] > 0
            ? $targetId
            : $this->firstAliveCreatureId($state);

        $this->recordTurn(
            $battle,
            BattleActorType::Enemy,
            $battle->enemy->id,
            null,
            $damage,
            0,
            ['effect' => 'damage', 'target_user_creature_id' => $targetId],
        );

        if (! $this->firstAliveCreatureId($state)) {
            return $this->finalizeBattle($battle, $state, BattleStatus::Lost);
        }

        $state['turn'] = ($state['turn'] ?? 1) + 1;
        $battle->forceFill(['state' => $state])->save();

        return $battle->refresh();
    }

    protected function resolveEnemySkillPower(Enemy $enemy): int
    {
        $skillSet = $enemy->skill_set ?? [];
        $firstSkill = $skillSet[0] ?? null;

        if (is_array($firstSkill) && isset($firstSkill['power'])) {
            return (int) $firstSkill['power'];
        }

        return 0;
    }

    protected function resolveTargetCreatureId(array $state): ?int
    {
        $activeId = $state['active_user_creature_id'] ?? null;

        if ($activeId && (($state['player_team'][(string) $activeId]['current_hp'] ?? 0) > 0)) {
            return (int) $activeId;
        }

        return $this->firstAliveCreatureId($state);
    }

    protected function firstAliveCreatureId(array $state): ?int
    {
        foreach ($state['player_team'] ?? [] as $id => $memberState) {
            if (($memberState['current_hp'] ?? 0) > 0) {
                return (int) $id;
            }
        }

        return null;
    }

    protected function recordTurn(
        Battle $battle,
        BattleActorType $actorType,
        int $actorId,
        ?Skill $skill,
        int $damage,
        int $healing,
        array $result,
    ): void {
        BattleTurn::query()->create([
            'battle_id' => $battle->id,
            'turn_number' => ((int) $battle->turns()->max('turn_number')) + 1,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'skill_id' => $skill?->id,
            'damage' => $damage,
            'healing' => $healing,
            'result' => $result,
        ]);
    }

    protected function finalizeBattle(Battle $battle, array $state, BattleStatus $status): Battle
    {
        $battle->forceFill([
            'state' => $state,
            'status' => $status,
            'ended_at' => now(),
        ])->save();

        $battle = $battle->refresh();

        if ($status === BattleStatus::Won) {
            return $this->grantRewards($battle);
        }

        return $battle;
    }

    protected function markLevelAsCompleted(Battle $battle): void
    {
        $battle->loadMissing('level.zone');

        $remainingHp = collect($battle->state['player_team'] ?? [])
            ->sum(fn (array $memberState) => $memberState['current_hp'] ?? 0);

        UserLevelProgress::query()->updateOrCreate(
            [
                'user_id' => $battle->user_id,
                'level_id' => $battle->level_id,
            ],
            [
                'status' => LevelProgressStatus::Completed,
                'best_result' => ['remaining_hp' => $remainingHp],
                'completed_at' => now(),
            ],
        );

        $nextLevel = Level::query()
            ->where('zone_id', $battle->level->zone_id)
            ->where('level_number', $battle->level->level_number + 1)
            ->first();

        if ($nextLevel) {
            UserLevelProgress::query()->updateOrCreate(
                [
                    'user_id' => $battle->user_id,
                    'level_id' => $nextLevel->id,
                ],
                [
                    'status' => LevelProgressStatus::Unlocked,
                ],
            );
        }

        if ($battle->level->unlocks_zone_id) {
            $this->unlockZone($battle->user_id, $battle->level->unlocks_zone_id);
        }
    }

    protected function unlockZone(int $userId, int $zoneId): void
    {
        $firstLevel = Level::query()
            ->where('zone_id', $zoneId)
            ->orderBy('level_number')
            ->first();

        if (! $firstLevel) {
            return;
        }

        UserLevelProgress::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'level_id' => $firstLevel->id,
            ],
            [
                'status' => LevelProgressStatus::Unlocked,
            ],
        );
    }
}
