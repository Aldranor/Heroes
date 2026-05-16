<?php

namespace App\Services;

use App\Enums\ArenaRunStatus;
use App\Enums\BattleStatus;
use App\Models\World\ArenaBattle;
use App\Models\World\ArenaRun;
use App\Models\World\Enemy;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ArenaService
{
    public function __construct(
        protected BattleService $battleService,
        protected RewardService $rewardService,
    ) {}

    public function startRun(User $user, array|Collection $team, array|Collection $enemies): ArenaRun
    {
        $enemyCollection = collect($enemies)
            ->map(fn ($enemy) => $enemy instanceof Enemy ? $enemy : Enemy::query()->findOrFail($enemy))
            ->values();

        if ($enemyCollection->count() !== 3) {
            throw new InvalidArgumentException('The MVP arena requires exactly three enemies.');
        }

        return ArenaRun::query()->create([
            'user_id' => $user->id,
            'status' => ArenaRunStatus::Ongoing,
            'current_stage' => 1,
            'reward_xp' => 0,
            'reward_coins' => $enemyCollection->sum(fn (Enemy $enemy) => max(10, (int) floor($enemy->reward_coins / 2))),
            'enemy_ids' => $enemyCollection->pluck('id')->all(),
            'state' => [
                'team_ids' => collect($team)->map(fn ($member) => is_object($member) ? $member->id : $member)->values()->all(),
            ],
            'started_at' => now(),
        ]);
    }

    public function startNextBattle(ArenaRun $arenaRun, array|Collection $team): ArenaBattle
    {
        $arenaRun->loadMissing('user');

        if ($arenaRun->status !== ArenaRunStatus::Ongoing) {
            throw new InvalidArgumentException('Only ongoing arena runs can start a new battle.');
        }

        $enemyId = $arenaRun->enemy_ids[$arenaRun->current_stage - 1] ?? null;

        if (! $enemyId) {
            throw new InvalidArgumentException('No arena enemy is configured for this stage.');
        }

        $enemy = Enemy::query()->findOrFail($enemyId);
        $battle = $this->battleService->startBattle($arenaRun->user, $enemy, $team);

        return ArenaBattle::query()->create([
            'arena_run_id' => $arenaRun->id,
            'battle_id' => $battle->id,
            'enemy_id' => $enemy->id,
            'stage_number' => $arenaRun->current_stage,
            'status' => BattleStatus::Ongoing,
            'started_at' => now(),
        ]);
    }

    public function recordBattleResult(ArenaBattle $arenaBattle): ArenaRun
    {
        $arenaBattle->loadMissing(['battle', 'arenaRun.user']);

        if ($arenaBattle->battle->status === BattleStatus::Ongoing) {
            throw new InvalidArgumentException('The arena battle is not finished yet.');
        }

        return DB::transaction(function () use ($arenaBattle) {
            $lockedArenaBattle = ArenaBattle::query()
                ->with(['battle', 'arenaRun.user'])
                ->lockForUpdate()
                ->findOrFail($arenaBattle->id);

            $arenaRun = $lockedArenaBattle->arenaRun;

            $lockedArenaBattle->update([
                'status' => $lockedArenaBattle->battle->status,
                'ended_at' => now(),
            ]);

            if ($lockedArenaBattle->battle->status === BattleStatus::Lost) {
                $arenaRun->update([
                    'status' => ArenaRunStatus::Lost,
                    'ended_at' => now(),
                ]);

                return $arenaRun->refresh();
            }

            if ($arenaRun->current_stage >= count($arenaRun->enemy_ids ?? [])) {
                $this->rewardService->grantCoins($arenaRun->user, $arenaRun->reward_coins);

                $arenaRun->update([
                    'status' => ArenaRunStatus::Won,
                    'ended_at' => now(),
                ]);

                return $arenaRun->refresh();
            }

            $arenaRun->increment('current_stage');

            return $arenaRun->refresh();
        });
    }
}
