<?php

namespace App\Services;

use App\Enums\QuestObjectiveType;
use App\Enums\QuestStatus;
use App\Models\Quests\Quest;
use App\Models\Quests\UserQuest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuestService
{
    public function __construct(
        protected RewardService $rewardService,
    ) {}

    public function acceptQuest(User $user, Quest $quest): UserQuest
    {
        if ($user->level < $quest->unlock_level) {
            throw new InvalidArgumentException('The player level is too low for this quest.');
        }

        return DB::transaction(function () use ($user, $quest) {
            $latest = UserQuest::query()
                ->where('user_id', $user->id)
                ->where('quest_id', $quest->id)
                ->latest('id')
                ->first();

            $canCreateNewRecord = ! $latest
                || (($quest->is_repeatable || $quest->is_daily) && $latest->status === QuestStatus::Claimed);

            if ($canCreateNewRecord) {
                return UserQuest::query()->create([
                    'user_id' => $user->id,
                    'quest_id' => $quest->id,
                    'status' => QuestStatus::Accepted,
                    'accepted_at' => now(),
                ]);
            }

            if ($latest->status === QuestStatus::Available) {
                $latest->update([
                    'status' => QuestStatus::Accepted,
                    'accepted_at' => now(),
                ]);
            }

            return $latest->refresh();
        });
    }

    public function updateProgress(
        User $user,
        QuestObjectiveType|string $objectiveType,
        int $amount = 1,
        array $context = [],
    ): Collection {
        $objectiveType = is_string($objectiveType)
            ? QuestObjectiveType::from($objectiveType)
            : $objectiveType;

        $updatedQuests = collect();

        UserQuest::query()
            ->with('quest')
            ->where('user_id', $user->id)
            ->where('status', QuestStatus::Accepted)
            ->whereHas('quest', fn ($query) => $query->where('objective_type', $objectiveType))
            ->get()
            ->each(function (UserQuest $userQuest) use ($amount, $context, $updatedQuests) {
                if (! $this->matchesObjectivePayload($userQuest->quest, $context)) {
                    return;
                }

                $userQuest->progress = min(
                    $userQuest->quest->objective_target,
                    $userQuest->progress + max(0, $amount),
                );
                $userQuest->save();

                if ($userQuest->progress >= $userQuest->quest->objective_target) {
                    $userQuest = $this->completeQuest($userQuest);
                }

                $updatedQuests->push($userQuest->refresh());
            });

        return $updatedQuests;
    }

    public function completeQuest(UserQuest $userQuest): UserQuest
    {
        if ($userQuest->status === QuestStatus::Claimed) {
            return $userQuest;
        }

        $userQuest->update([
            'status' => QuestStatus::Completed,
            'completed_at' => now(),
        ]);

        return $userQuest->refresh();
    }

    public function claimRewards(UserQuest $userQuest): UserQuest
    {
        $userQuest->loadMissing(['user', 'quest.rewardEquipment', 'quest.rewardAvatarItem', 'quest.rewardCreature']);

        if ($userQuest->status !== QuestStatus::Completed) {
            throw new InvalidArgumentException('Only completed quests can be claimed.');
        }

        return DB::transaction(function () use ($userQuest) {
            $lockedQuest = UserQuest::query()
                ->with(['user', 'quest.rewardEquipment', 'quest.rewardAvatarItem', 'quest.rewardCreature'])
                ->lockForUpdate()
                ->findOrFail($userQuest->id);

            $this->rewardService->grantUserXp($lockedQuest->user, $lockedQuest->quest->reward_xp);
            $this->rewardService->grantCoins($lockedQuest->user, $lockedQuest->quest->reward_coins);
            $this->rewardService->grantSkillPoints($lockedQuest->user, (int) ($lockedQuest->quest->reward_skill_points ?? 0));
            $this->rewardService->grantSpecialSkillPoints($lockedQuest->user, (int) ($lockedQuest->quest->reward_special_skill_points ?? 0));

            if ($lockedQuest->quest->rewardEquipment) {
                $this->rewardService->grantEquipment($lockedQuest->user, $lockedQuest->quest->rewardEquipment);
            }

            if ($lockedQuest->quest->rewardAvatarItem) {
                $this->rewardService->grantAvatarItem($lockedQuest->user, $lockedQuest->quest->rewardAvatarItem);
            }

            if ($lockedQuest->quest->rewardCreature) {
                $this->rewardService->grantCreature($lockedQuest->user, $lockedQuest->quest->rewardCreature);
            }

            $lockedQuest->update([
                'status' => QuestStatus::Claimed,
                'claimed_at' => now(),
            ]);

            return $lockedQuest->refresh();
        });
    }

    protected function matchesObjectivePayload(Quest $quest, array $context): bool
    {
        $payload = $quest->objective_payload ?? [];

        if ($payload === []) {
            return true;
        }

        foreach ($payload as $key => $expectedValue) {
            if (! array_key_exists($key, $context)) {
                return false;
            }

            $actualValue = $context[$key];

            if (is_array($expectedValue) && ! in_array($actualValue, $expectedValue, true)) {
                return false;
            }

            if (! is_array($expectedValue) && $actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }
}
