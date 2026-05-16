<?php

namespace App\Services;

use App\Models\Companions\UserCompanion;
use App\Models\Creatures\UserCreature;
use App\Models\Learning\LearningCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class XpService
{
    public function xpRequiredForLevel(int $level): int
    {
        return (int) round(100 * (1.5 ** max(0, $level - 1)));
    }

    public function calculateUserTrainingXp(int $correctAnswers, int $totalQuestions): int
    {
        $incorrectAnswers = max(0, $totalQuestions - $correctAnswers);
        $xp = ($correctAnswers * $this->calculateUserAnswerXp(true))
            + ($incorrectAnswers * $this->calculateUserAnswerXp(false));

        $xp += $this->calculateUserTrainingCompletionBonus($correctAnswers, $totalQuestions);

        return $xp;
    }

    public function calculateUserAnswerXp(bool $isCorrect): int
    {
        return $isCorrect ? 10 : 3;
    }

    public function calculateUserTrainingCompletionBonus(int $correctAnswers, int $totalQuestions): int
    {
        return $correctAnswers === $totalQuestions && $totalQuestions > 0 ? 20 : 0;
    }

    public function calculateCreatureTrainingXp(
        UserCreature $userCreature,
        ?LearningCategory $questionCategory,
        bool $isCorrect,
    ): int {
        $isSpecialized = $userCreature->isSpecializedFor($questionCategory);

        $xp = match (true) {
            $isCorrect && $isSpecialized => 15,
            $isCorrect => 8,
            default => 5,
        };

        if ($isSpecialized) {
            $xp = (int) round($xp * 1.2);
        }

        return $xp;
    }

    public function calculateCompanionAnswerXp(
        UserCompanion $userCompanion,
        ?LearningCategory $questionCategory,
        bool $isCorrect,
    ): int {
        return match (true) {
            $isCorrect && $userCompanion->isSpecializedFor($questionCategory) => 15,
            $isCorrect => 8,
            default => 5,
        };
    }

    public function grantUserXp(User $user, int $amount): User
    {
        if ($amount <= 0) {
            return $user->refresh();
        }

        return DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->xp += $amount;
            $levelsGained = 0;

            while ($lockedUser->xp >= $this->xpRequiredForLevel($lockedUser->level)) {
                $lockedUser->xp -= $this->xpRequiredForLevel($lockedUser->level);
                $lockedUser->level++;
                $levelsGained++;
            }

            if ($levelsGained > 0) {
                $lockedUser->skill_points += $levelsGained;
            }

            $lockedUser->save();

            return $lockedUser->refresh();
        });
    }

    public function grantCreatureXp(UserCreature $userCreature, int $amount): UserCreature
    {
        if ($amount <= 0) {
            return $userCreature->refresh();
        }

        return DB::transaction(function () use ($userCreature, $amount) {
            $lockedCreature = UserCreature::query()
                ->with(['creature.skills'])
                ->lockForUpdate()
                ->findOrFail($userCreature->id);

            $lockedCreature->xp += $amount;
            $didLevelUp = false;

            while ($lockedCreature->xp >= $this->xpRequiredForLevel($lockedCreature->level)) {
                $lockedCreature->xp -= $this->xpRequiredForLevel($lockedCreature->level);
                $lockedCreature->level++;
                $didLevelUp = true;
            }

            if ($didLevelUp || ! $lockedCreature->current_hp) {
                $lockedCreature->current_hp = $lockedCreature->maxHp();
            }

            $lockedCreature->save();
            $this->unlockSkills($lockedCreature);

            return $lockedCreature->refresh();
        });
    }

    public function grantCompanionXp(UserCompanion $userCompanion, int $amount): UserCompanion
    {
        if ($amount <= 0) {
            return $userCompanion->refresh();
        }

        return DB::transaction(function () use ($userCompanion, $amount) {
            $lockedCompanion = UserCompanion::query()
                ->with(['companion.skills'])
                ->lockForUpdate()
                ->findOrFail($userCompanion->id);

            $lockedCompanion->xp += $amount;
            $levelsGained = 0;

            while ($lockedCompanion->xp >= $this->xpRequiredForLevel($lockedCompanion->level)) {
                $lockedCompanion->xp -= $this->xpRequiredForLevel($lockedCompanion->level);
                $lockedCompanion->level++;
                $levelsGained++;
            }

            if ($levelsGained > 0) {
                $lockedCompanion->talent_points += $levelsGained;
                $lockedCompanion->current_hp = $lockedCompanion->maxHp();
            }

            $lockedCompanion->save();
            $this->unlockCompanionSkills($lockedCompanion);

            return $lockedCompanion->refresh();
        });
    }

    public function unlockSkills(UserCreature $userCreature): Collection
    {
        $userCreature->loadMissing('creature.skills');

        $availableSkills = $userCreature->creature->skills
            ->filter(fn ($skill) => $skill->pivot->unlock_level <= $userCreature->level);

        $newlyUnlocked = collect();

        foreach ($availableSkills as $skill) {
            $record = $userCreature->userCreatureSkills()->firstOrCreate(
                ['skill_id' => $skill->id],
                ['unlocked_at' => now()],
            );

            if ($record->wasRecentlyCreated) {
                $newlyUnlocked->push($skill);
            }
        }

        return $newlyUnlocked;
    }

    public function unlockCompanionSkills(UserCompanion $userCompanion): Collection
    {
        $userCompanion->loadMissing('companion.skills');

        $availableSkills = $userCompanion->companion->skills
            ->filter(fn ($skill) => $skill->pivot->unlock_level <= $userCompanion->level);

        $newlyUnlocked = collect();

        foreach ($availableSkills as $skill) {
            $record = $userCompanion->userCompanionSkills()->firstOrCreate(
                ['skill_id' => $skill->id],
                ['unlocked_at' => now()],
            );

            if ($record->wasRecentlyCreated) {
                $newlyUnlocked->push($skill);
            }
        }

        return $newlyUnlocked;
    }
}
