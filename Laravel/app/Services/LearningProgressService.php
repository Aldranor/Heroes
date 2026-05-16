<?php

namespace App\Services;

use App\Enums\MasteryStatus;
use App\Enums\QuestionDifficulty;
use App\Models\Learning\Question;
use App\Models\Learning\UserLearningItemProgress;
use App\Models\Lessons\Flashcard;
use App\Models\Lessons\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningProgressService
{
    public const ITEM_CATEGORY = 'category';
    public const ITEM_FLASHCARD = 'flashcard';
    public const ITEM_QUESTION = 'question';

    public function registerFlashcardReview(
        User $user,
        Flashcard $flashcard,
        Lesson $lesson,
        bool $mastered = false,
    ): UserLearningItemProgress {
        $progress = UserLearningItemProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'item_type' => self::ITEM_FLASHCARD,
            'item_key' => (string) $flashcard->id,
        ]);

        $progress->fill([
            'learning_domain_id' => $lesson->learning_domain_id,
            'learning_category_id' => $lesson->learning_category_id,
            'learning_topic_id' => $lesson->learning_topic_id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->review_count = (int) ($progress->review_count ?? 0) + 1;
        $progress->attempts = (int) ($progress->attempts ?? 0) + 1;
        $progress->correct_attempts = (int) ($progress->correct_attempts ?? 0) + ($mastered ? 1 : 0);
        $progress->difficulty_weight = max((int) ($progress->difficulty_weight ?? 0), $this->difficultyWeight($lesson->difficulty));
        $progress->last_interacted_at = now();

        $progress->metadata = array_merge($progress->metadata ?? [], [
            'last_result' => $mastered ? 'mastered' : 'reviewed',
            'visual_label' => $flashcard->visual_label,
        ]);

        $progress->mastery_score = $this->flashcardScore($progress);
        $progress->mastery_status = $this->statusFromScore($progress->mastery_score);
        $progress->mastered_at = in_array($progress->mastery_status, [MasteryStatus::Mastered, MasteryStatus::Automated], true)
            ? ($progress->mastered_at ?? now())
            : null;
        $progress->save();

        $this->syncCategoryProgress($user, $lesson->learning_category_id, $lesson->learning_domain_id);

        return $progress->refresh();
    }

    public function registerQuestionResult(
        User $user,
        Question $question,
        bool $isCorrect,
        ?int $timeSpentSeconds = null,
        ?Lesson $lesson = null,
    ): UserLearningItemProgress {
        $progress = UserLearningItemProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'item_type' => self::ITEM_QUESTION,
            'item_key' => (string) $question->id,
        ]);

        $progress->fill([
            'learning_domain_id' => $question->learning_domain_id,
            'learning_category_id' => $question->learning_category_id,
            'learning_topic_id' => $lesson?->learning_topic_id,
            'lesson_id' => $lesson?->id,
        ]);

        $progress->attempts = (int) ($progress->attempts ?? 0) + 1;
        $progress->review_count = (int) ($progress->review_count ?? 0) + 1;
        $progress->correct_attempts = (int) ($progress->correct_attempts ?? 0) + ($isCorrect ? 1 : 0);
        $progress->error_count = (int) ($progress->error_count ?? 0) + ($isCorrect ? 0 : 1);
        $progress->difficulty_weight = max((int) ($progress->difficulty_weight ?? 0), $this->difficultyWeight($question->difficulty));
        $progress->average_response_seconds = $this->updatedAverageSeconds(
            (int) ($progress->average_response_seconds ?? 0),
            $progress->review_count,
            $timeSpentSeconds,
        );
        $progress->last_interacted_at = now();

        $progress->metadata = array_merge($progress->metadata ?? [], [
            'last_type' => $question->type->value,
            'last_result' => $isCorrect ? 'correct' : 'incorrect',
        ]);

        $progress->mastery_score = $this->questionScore($progress);
        $progress->mastery_status = $this->statusFromScore($progress->mastery_score);
        $progress->mastered_at = in_array($progress->mastery_status, [MasteryStatus::Mastered, MasteryStatus::Automated], true)
            ? ($progress->mastered_at ?? now())
            : null;
        $progress->save();

        $this->syncCategoryProgress($user, $question->learning_category_id, $question->learning_domain_id);

        return $progress->refresh();
    }

    public function categoryProgressMap(User $user, array $categoryIds): Collection
    {
        if ($categoryIds === []) {
            return collect();
        }

        return UserLearningItemProgress::query()
            ->where('user_id', $user->id)
            ->where('item_type', self::ITEM_CATEGORY)
            ->whereIn('learning_category_id', $categoryIds)
            ->get()
            ->keyBy('learning_category_id');
    }

    protected function syncCategoryProgress(User $user, ?int $categoryId, ?int $domainId): void
    {
        if (! $categoryId || ! $domainId) {
            return;
        }

        $itemProgress = UserLearningItemProgress::query()
            ->where('user_id', $user->id)
            ->where('learning_category_id', $categoryId)
            ->whereIn('item_type', [self::ITEM_FLASHCARD, self::ITEM_QUESTION])
            ->get();

        if ($itemProgress->isEmpty()) {
            return;
        }

        $progress = UserLearningItemProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'item_type' => self::ITEM_CATEGORY,
            'item_key' => (string) $categoryId,
        ]);

        $masteryScore = (int) round($itemProgress->avg('mastery_score'));

        $progress->fill([
            'learning_domain_id' => $domainId,
            'learning_category_id' => $categoryId,
            'mastery_score' => $masteryScore,
            'mastery_status' => $this->statusFromScore($masteryScore),
            'attempts' => (int) $itemProgress->sum('attempts'),
            'correct_attempts' => (int) $itemProgress->sum('correct_attempts'),
            'error_count' => (int) $itemProgress->sum('error_count'),
            'review_count' => (int) $itemProgress->sum('review_count'),
            'difficulty_weight' => (int) $itemProgress->max('difficulty_weight'),
            'average_response_seconds' => (int) round($itemProgress->avg('average_response_seconds')),
            'last_interacted_at' => $itemProgress->sortByDesc('last_interacted_at')->first()?->last_interacted_at,
            'mastered_at' => $masteryScore >= 60 ? ($progress->mastered_at ?? now()) : null,
            'metadata' => [
                'items' => $itemProgress->count(),
                'automated_items' => $itemProgress->filter(
                    fn (UserLearningItemProgress $item): bool => $item->mastery_status === MasteryStatus::Automated,
                )->count(),
                'mastered_items' => $itemProgress->filter(
                    fn (UserLearningItemProgress $item): bool => in_array($item->mastery_status, [MasteryStatus::Mastered, MasteryStatus::Automated], true),
                )->count(),
            ],
        ]);

        $progress->save();
    }

    protected function flashcardScore(UserLearningItemProgress $progress): int
    {
        $repetitionScore = min(36, $progress->review_count * 9);
        $masteryBonus = min(24, $progress->correct_attempts * 12);
        $difficultyBonus = min(16, $progress->difficulty_weight);
        $errorPenalty = min(12, $progress->error_count * 2);

        return $this->clampScore($repetitionScore + $masteryBonus + $difficultyBonus - $errorPenalty);
    }

    protected function questionScore(UserLearningItemProgress $progress): int
    {
        $accuracy = $progress->attempts > 0
            ? ($progress->correct_attempts / $progress->attempts) * 46
            : 0;
        $speed = $this->speedScore($progress->average_response_seconds);
        $repetition = min(20, $progress->review_count * 4);
        $difficultyBonus = min(16, $progress->difficulty_weight);
        $errorPenalty = min(18, $progress->error_count * 2);

        return $this->clampScore((int) round($accuracy + $speed + $repetition + $difficultyBonus - $errorPenalty));
    }

    protected function speedScore(int $averageResponseSeconds): int
    {
        if ($averageResponseSeconds <= 0) {
            return 10;
        }

        if ($averageResponseSeconds <= 8) {
            return 18;
        }

        if ($averageResponseSeconds <= 14) {
            return 14;
        }

        if ($averageResponseSeconds <= 20) {
            return 10;
        }

        if ($averageResponseSeconds <= 30) {
            return 6;
        }

        return 2;
    }

    protected function updatedAverageSeconds(int $currentAverage, int $reviewCount, ?int $timeSpentSeconds): int
    {
        if ($timeSpentSeconds === null || $timeSpentSeconds <= 0) {
            return $currentAverage;
        }

        if ($reviewCount <= 1) {
            return $timeSpentSeconds;
        }

        return (int) round((($currentAverage * ($reviewCount - 1)) + $timeSpentSeconds) / $reviewCount);
    }

    protected function difficultyWeight(QuestionDifficulty $difficulty): int
    {
        return match ($difficulty) {
            QuestionDifficulty::Easy => 4,
            QuestionDifficulty::Medium => 8,
            QuestionDifficulty::Hard => 12,
            QuestionDifficulty::Expert => 16,
        };
    }

    protected function statusFromScore(int $score): MasteryStatus
    {
        return match (true) {
            $score >= 85 => MasteryStatus::Automated,
            $score >= 60 => MasteryStatus::Mastered,
            $score >= 30 => MasteryStatus::Learning,
            default => MasteryStatus::Unknown,
        };
    }

    protected function clampScore(int|float $score): int
    {
        return max(0, min(100, (int) round($score)));
    }
}
