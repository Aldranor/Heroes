<?php

namespace App\Services;

use App\Enums\FlashcardProgressStatus;
use App\Enums\LessonProgressStatus;
use App\Enums\LessonStatus;
use App\Enums\MasteryStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestObjectiveType;
use App\Enums\QuestStatus;
use App\Models\Learning\Answer;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\LearningTopic;
use App\Models\Learning\UserLearningItemProgress;
use App\Models\Lessons\Flashcard;
use App\Models\Lessons\Lesson;
use App\Models\Lessons\UserFlashcardProgress;
use App\Models\Lessons\UserLessonProgress;
use App\Models\Quests\Quest;
use App\Models\Quests\UserQuest;
use App\Models\Training\TrainingAnswer;
use App\Models\User;
use App\Models\World\Enemy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LessonService
{
    public function __construct(
        protected RewardService $rewardService,
        protected QuestService $questService,
        protected LearningProgressService $learningProgressService,
        protected QuestionPresentationService $questionPresentationService,
    ) {}

    public function classroomData(User $user): array
    {
        $lessons = Lesson::query()
            ->published()
            ->with(['learningDomain', 'learningTopic', 'learningCategory'])
            ->with(['progressRecords' => fn ($query) => $query->where('user_id', $user->id)])
            ->withCount([
                'flashcards',
                'questions' => fn ($query) => $query->where('status', QuestionStatus::Published),
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return [
            'lessons' => $lessons,
            'completedLessons' => $lessons->filter(fn (Lesson $lesson): bool => $this->progressFromLoadedLesson($lesson)?->quiz_passed === true)->count(),
        ];
    }

    public function libraryData(User $user, ?int $selectedDomainId = null, ?int $selectedTopicId = null): array
    {
        $domains = LearningDomain::query()
            ->where('is_active', true)
            ->whereHas('topics.lessons', fn ($query) => $query->published())
            ->with(['topics' => function ($query) use ($user) {
                $query
                    ->whereHas('lessons', fn ($lessonQuery) => $lessonQuery->published())
                    ->with(['lessons' => fn ($lessonQuery) => $lessonQuery
                        ->published()
                        ->with([
                            'learningDomain',
                            'learningTopic',
                            'learningCategory',
                            'flashcards' => fn ($flashcardQuery) => $flashcardQuery->with([
                                'progressRecords' => fn ($progressQuery) => $progressQuery->where('user_id', $user->id),
                            ]),
                            'progressRecords' => fn ($progressQuery) => $progressQuery->where('user_id', $user->id),
                        ])
                        ->withCount([
                            'flashcards',
                            'questions' => fn ($questionQuery) => $questionQuery->where('status', QuestionStatus::Published),
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('title')])
                    ->orderBy('sort_order')
                    ->orderBy('title');
            }])
            ->orderBy('name')
            ->get();

        $categoryProgress = $this->learningProgressService->categoryProgressMap(
            $user,
            $domains
                ->flatMap(fn (LearningDomain $domain) => $domain->categories->pluck('id'))
                ->unique()
                ->values()
                ->all(),
        );

        $this->decorateLibraryDomains($user, $domains, $categoryProgress);

        $selectedDomain = $domains->firstWhere('id', $selectedDomainId) ?? $domains->first();
        $selectedTopic = $selectedDomain && $selectedTopicId
            ? $selectedDomain->topics->firstWhere('id', $selectedTopicId)
            : null;

        return [
            'domains' => $domains,
            'selectedDomain' => $selectedDomain,
            'selectedTopic' => $selectedTopic,
            'visibleTopics' => $selectedTopic ? collect([$selectedTopic]) : ($selectedDomain?->topics ?? collect()),
            'recommendation' => $this->recommendationFor($user, $domains),
            'stats' => [
                'lessons' => $domains->flatMap(fn (LearningDomain $domain) => $domain->topics)->flatMap(fn (LearningTopic $topic) => $topic->lessons)->count(),
                'mastered' => $domains->flatMap(fn (LearningDomain $domain) => $domain->topics)->flatMap(fn (LearningTopic $topic) => $topic->lessons)->filter(fn (Lesson $lesson): bool => $lesson->getAttribute('library_status_key') === 'mastered')->count(),
            ],
        ];
    }

    public function lessonData(User $user, Lesson $lesson): array
    {
        $this->assertPublished($lesson);

        $progress = $this->startOrResume($user, $lesson);

        $lesson->load([
            'learningDomain',
            'learningTopic',
            'learningCategory',
            'sections',
            'flashcards' => fn ($query) => $query->with(['progressRecords' => fn ($progressQuery) => $progressQuery->where('user_id', $user->id)]),
            'questions' => fn ($query) => $query
                ->where('status', QuestionStatus::Published)
                ->with('answers'),
        ]);

        $lesson->setRelation('questions', $this->questionPresentationService->decorateCollection($lesson->questions));
        $this->decorateLessonFlashcards($user, $lesson);

        return [
            'lesson' => $lesson,
            'progress' => $progress->refresh(),
            'progressPercent' => $this->progressPercent($user, $lesson),
            'viewedFlashcards' => $this->viewedFlashcardCount($user, $lesson),
            'categoryMastery' => $lesson->learning_category_id
                ? $this->learningProgressService->categoryProgressMap($user, [$lesson->learning_category_id])->get($lesson->learning_category_id)
                : null,
        ];
    }

    public function startOrResume(User $user, Lesson $lesson): UserLessonProgress
    {
        $this->assertPublished($lesson);

        return DB::transaction(function () use ($user, $lesson) {
            $progress = UserLessonProgress::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'status' => LessonProgressStatus::InProgress,
                    'started_at' => now(),
                    'last_seen_at' => now(),
                ],
            );

            $updates = ['last_seen_at' => now()];

            if ($progress->status === LessonProgressStatus::NotStarted) {
                $updates['status'] = LessonProgressStatus::InProgress;
                $updates['started_at'] = $progress->started_at ?? now();
            }

            $progress->forceFill($updates)->save();

            return $progress->refresh();
        });
    }

    public function markFlashcardViewed(
        User $user,
        Lesson $lesson,
        Flashcard $flashcard,
        bool $mastered = false,
    ): UserFlashcardProgress {
        $this->assertPublished($lesson);

        if ($flashcard->lesson_id !== $lesson->id) {
            throw new InvalidArgumentException('Cette flashcard ne fait pas partie du module.');
        }

        $this->startOrResume($user, $lesson);

        return DB::transaction(function () use ($user, $flashcard, $mastered) {
            $progress = UserFlashcardProgress::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'flashcard_id' => $flashcard->id,
                ],
                [
                    'status' => $mastered ? FlashcardProgressStatus::Mastered : FlashcardProgressStatus::Viewed,
                    'first_viewed_at' => now(),
                ],
            );

            $progress->forceFill([
                'status' => $mastered ? FlashcardProgressStatus::Mastered : $progress->status,
                'flipped_count' => $progress->flipped_count + 1,
                'first_viewed_at' => $progress->first_viewed_at ?? now(),
                'last_viewed_at' => now(),
            ])->save();

            $this->learningProgressService->registerFlashcardReview(
                $user,
                $flashcard->loadMissing('lesson'),
                $flashcard->lesson,
                $mastered,
            );

            return $progress->refresh();
        });
    }

    public function submitQuiz(User $user, Lesson $lesson, array $submittedAnswers): UserLessonProgress
    {
        $this->assertPublished($lesson);

        return DB::transaction(function () use ($user, $lesson, $submittedAnswers) {
            $progress = UserLessonProgress::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'lesson_id' => $lesson->id,
                    ],
                    [
                        'status' => LessonProgressStatus::InProgress,
                        'started_at' => now(),
                    ],
                );

            $questions = $lesson->questions()
                ->where('status', QuestionStatus::Published)
                ->with('answers')
                ->get();

            if ($questions->isEmpty()) {
                throw new InvalidArgumentException('Ce module ne contient pas encore de mini quiz.');
            }

            $correctAnswers = 0;
            $answerReview = [];

            foreach ($questions as $question) {
                $answerId = (int) ($submittedAnswers[$question->id] ?? 0);

                if ($answerId <= 0) {
                    throw new InvalidArgumentException('Répondez à toutes les questions du mini quiz.');
                }

                $answer = Answer::query()->findOrFail($answerId);

                if ($answer->question_id !== $question->id) {
                    throw new InvalidArgumentException('Une réponse ne correspond pas au mini quiz.');
                }

                $isCorrect = $answer->is_correct;
                $correctAnswers += $isCorrect ? 1 : 0;
                $answerReview[$question->id] = [
                    'answer_id' => $answer->id,
                    'is_correct' => $isCorrect,
                ];

                $this->learningProgressService->registerQuestionResult($user, $question, $isCorrect, null, $lesson);
            }

            $totalQuestions = $questions->count();
            $scorePercent = (int) round(($correctAnswers / $totalQuestions) * 100);
            $attemptPassed = $scorePercent >= $lesson->quiz_pass_score;
            $wasAlreadyPassed = $progress->quiz_passed;
            $passed = $wasAlreadyPassed || $attemptPassed;
            $xpAwarded = (int) ($progress->academic_xp_awarded ?? 0);

            if ($attemptPassed && $xpAwarded === 0) {
                $this->rewardService->grantUserXp($user, $lesson->academic_xp_reward);
                $xpAwarded = $lesson->academic_xp_reward;
            }

            $progress->forceFill([
                'status' => $passed ? LessonProgressStatus::Completed : LessonProgressStatus::InProgress,
                'quiz_score' => $correctAnswers,
                'quiz_total' => $totalQuestions,
                'quiz_passed' => $passed,
                'academic_xp_awarded' => $xpAwarded,
                'quiz_answers' => $answerReview,
                'training_unlocked_at' => $passed ? ($progress->training_unlocked_at ?? now()) : null,
                'completed_at' => $passed ? ($progress->completed_at ?? now()) : null,
                'last_seen_at' => now(),
            ])->save();

            if ($attemptPassed && ! $wasAlreadyPassed) {
                $this->questService->updateProgress($user, QuestObjectiveType::CompleteLessons, 1, [
                    'lesson_id' => $lesson->id,
                    'learning_domain_id' => $lesson->learning_domain_id,
                    'learning_category_id' => $lesson->learning_category_id,
                ]);
            }

            return $progress->refresh();
        });
    }

    public function trainingUnlockedFor(
        User $user,
        LearningDomain $learningDomain,
        LearningCategory $learningCategory,
    ): bool {
        return $this->requiredLessonForTraining($user, $learningDomain, $learningCategory) === null;
    }

    public function requiredLessonForTraining(
        User $user,
        LearningDomain $learningDomain,
        LearningCategory $learningCategory,
    ): ?Lesson {
        $categoryLessons = $this->trainingUnlockLessons($learningDomain, $learningCategory)->get();

        if ($categoryLessons->isEmpty()) {
            $categoryLessons = $this->domainTrainingUnlockLessons($learningDomain)->get();
        }

        $passedLessonIds = $categoryLessons->isEmpty()
            ? collect()
            : UserLessonProgress::query()
                ->where('user_id', $user->id)
                ->where('quiz_passed', true)
                ->whereIn('lesson_id', $categoryLessons->pluck('id'))
                ->pluck('lesson_id')
                ->map(fn (int|string $lessonId): int => (int) $lessonId);

        return $this->requiredLessonFromProgress($categoryLessons, $passedLessonIds);
    }

    public function decorateCategoriesForTraining(User $user, EloquentCollection $domains): EloquentCollection
    {
        $domainIds = $domains->pluck('id')->all();

        if ($domainIds === []) {
            return $domains;
        }

        $categoryIds = $domains
            ->flatMap(fn (LearningDomain $domain) => $domain->categories->pluck('id'))
            ->all();

        $unlockLessons = Lesson::query()
            ->published()
            ->where('unlocks_training', true)
            ->whereIn('learning_domain_id', $domainIds)
            ->where(function ($query) use ($categoryIds) {
                $query->whereNull('learning_category_id');

                if ($categoryIds !== []) {
                    $query->orWhereIn('learning_category_id', $categoryIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $passedLessonIds = $unlockLessons->isEmpty()
            ? collect()
            : UserLessonProgress::query()
                ->where('user_id', $user->id)
                ->where('quiz_passed', true)
                ->whereIn('lesson_id', $unlockLessons->pluck('id'))
                ->pluck('lesson_id')
                ->map(fn (int|string $lessonId): int => (int) $lessonId);

        $lessonsByRequirement = $unlockLessons->groupBy(
            fn (Lesson $lesson): string => $lesson->learning_category_id
                ? 'category:'.$lesson->learning_category_id
                : 'domain:'.$lesson->learning_domain_id,
        );

        $domains->each(function (LearningDomain $domain) use ($lessonsByRequirement, $passedLessonIds) {
            $domain->categories->each(function (LearningCategory $category) use ($domain, $lessonsByRequirement, $passedLessonIds) {
                $lessons = $lessonsByRequirement->get('category:'.$category->id, collect());

                if ($lessons->isEmpty()) {
                    $lessons = $lessonsByRequirement->get('domain:'.$domain->id, collect());
                }

                $requiredLesson = $this->requiredLessonFromProgress($lessons, $passedLessonIds);

                $category->setAttribute('training_unlocked', $requiredLesson === null);
                $category->setAttribute('required_lesson_id', $requiredLesson?->id);
                $category->setAttribute('required_lesson_title', $requiredLesson?->title);
            });
        });

        return $domains;
    }

    public function progressPercent(User $user, Lesson $lesson): int
    {
        $progress = UserLessonProgress::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if (! $progress) {
            return 0;
        }

        if ($progress->quiz_passed) {
            return 100;
        }

        $flashcardCount = max(1, $lesson->flashcards()->count());
        $viewedFlashcards = $this->viewedFlashcardCount($user, $lesson);

        return min(90, 25 + (int) floor(($viewedFlashcards / $flashcardCount) * 45));
    }

    protected function viewedFlashcardCount(User $user, Lesson $lesson): int
    {
        return UserFlashcardProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('flashcard_id', $lesson->flashcards()->select('id'))
            ->count();
    }

    protected function progressFromLoadedLesson(Lesson $lesson): ?UserLessonProgress
    {
        if (! $lesson->relationLoaded('progressRecords')) {
            return null;
        }

        return $lesson->progressRecords->first();
    }

    protected function decorateLibraryDomains(User $user, EloquentCollection $domains, Collection $categoryProgress): void
    {
        $domainIds = $domains->pluck('id')->all();

        if ($domainIds === []) {
            return;
        }

        $quests = Quest::query()
            ->whereIn('learning_domain_id', $domainIds)
            ->get();
        $activeQuestIds = UserQuest::query()
            ->where('user_id', $user->id)
            ->where('status', QuestStatus::Accepted)
            ->pluck('quest_id')
            ->all();
        $bossesByDomain = Enemy::query()
            ->whereIn('learning_domain_id', $domainIds)
            ->where('is_boss', true)
            ->orderBy('level')
            ->get()
            ->groupBy('learning_domain_id');

        $domains->each(function (LearningDomain $domain) use ($quests, $activeQuestIds, $bossesByDomain, $categoryProgress) {
            $domain->topics->each(function (LearningTopic $topic) use ($quests, $activeQuestIds, $bossesByDomain, $categoryProgress) {
                $topic->lessons->each(function (Lesson $lesson) use ($quests, $activeQuestIds, $bossesByDomain, $categoryProgress) {
                    $progressPercent = $this->libraryLessonProgressPercent($lesson);
                    $progress = $this->progressFromLoadedLesson($lesson);
                    $activeQuest = $quests->first(fn (Quest $quest): bool => in_array($quest->id, $activeQuestIds, true) && $this->questMatchesLesson($quest, $lesson));
                    $linkedQuest = $activeQuest ?? $quests->first(fn (Quest $quest): bool => $this->questMatchesLesson($quest, $lesson));
                    $boss = $bossesByDomain->get($lesson->learning_domain_id, collect())->first();
                    $masteryProgress = $lesson->learning_category_id ? $categoryProgress->get($lesson->learning_category_id) : null;
                    $flashcards = $lesson->relationLoaded('flashcards') ? $lesson->flashcards : collect();
                    $flashcardCount = $flashcards->count();
                    $viewedFlashcards = $flashcards->filter(fn (Flashcard $flashcard): bool => $flashcard->progressRecords->isNotEmpty())->count();
                    $masteredFlashcards = $flashcards->filter(
                        fn (Flashcard $flashcard): bool => $flashcard->progressRecords->first()?->status === FlashcardProgressStatus::Mastered,
                    )->count();
                    $nextStepKey = $this->libraryLessonNextStepKey($lesson, $progress, $flashcardCount, $viewedFlashcards);
                    $nextAction = $this->libraryLessonNextAction($lesson, $nextStepKey);

                    $lesson->setAttribute('library_progress_percent', $progressPercent);
                    $lesson->setAttribute('library_status_key', $this->libraryLessonStatusKey($lesson, $progressPercent));
                    $lesson->setAttribute('library_status_label', __("lessons.library.status.{$lesson->getAttribute('library_status_key')}"));
                    $lesson->setAttribute('library_difficulty_label', __("lessons.library.difficulty.{$lesson->difficulty->value}"));
                    $lesson->setAttribute('library_mastery_key', $masteryProgress?->mastery_status?->value ?? MasteryStatus::Unknown->value);
                    $lesson->setAttribute('library_mastery_label', __("lessons.mastery.status.{$lesson->getAttribute('library_mastery_key')}"));
                    $lesson->setAttribute('library_mastery_score', $masteryProgress?->mastery_score ?? 0);
                    $lesson->setAttribute('library_linked_quest', $linkedQuest);
                    $lesson->setAttribute('library_active_quest', $activeQuest);
                    $lesson->setAttribute('library_boss', $boss);
                    $lesson->setAttribute('library_training_locked', $progress?->quiz_passed !== true);
                    $lesson->setAttribute('library_flashcards_count', $flashcardCount);
                    $lesson->setAttribute('library_viewed_flashcards_count', $viewedFlashcards);
                    $lesson->setAttribute('library_mastered_flashcards_count', $masteredFlashcards);
                    $lesson->setAttribute('library_remaining_flashcards_count', max(0, $flashcardCount - $viewedFlashcards));
                    $lesson->setAttribute('library_questions_count', (int) $lesson->questions_count);
                    $lesson->setAttribute('library_next_step_key', $nextStepKey);
                    $lesson->setAttribute('library_next_step_title', __("lessons.library.journey.steps.{$nextStepKey}.title"));
                    $lesson->setAttribute('library_next_step_body', __("lessons.library.journey.steps.{$nextStepKey}.body"));
                    $lesson->setAttribute('library_next_action_label', __("lessons.library.actions.{$nextAction['label']}"));
                    $lesson->setAttribute('library_next_action_url', $nextAction['url']);
                });

                $topicProgress = $topic->lessons->isEmpty()
                    ? 0
                    : (int) round($topic->lessons->avg(fn (Lesson $lesson): int => $lesson->getAttribute('library_progress_percent')));

                $topic->setAttribute('library_progress_percent', $topicProgress);
                $topic->setAttribute('library_mastered_count', $topic->lessons->filter(fn (Lesson $lesson): bool => $lesson->getAttribute('library_status_key') === 'mastered')->count());
                $topic->setAttribute('library_lessons_count', $topic->lessons->count());
            });

            $domainProgress = $domain->topics->isEmpty()
                ? 0
                : (int) round($domain->topics->avg(fn (LearningTopic $topic): int => $topic->getAttribute('library_progress_percent')));

            $domain->setAttribute('library_progress_percent', $domainProgress);
        });
    }

    protected function libraryLessonProgressPercent(Lesson $lesson): int
    {
        $progress = $this->progressFromLoadedLesson($lesson);

        if (! $progress) {
            return 0;
        }

        if ($progress->quiz_passed) {
            return 100;
        }

        $flashcards = $lesson->relationLoaded('flashcards') ? $lesson->flashcards : collect();
        $flashcardCount = max(1, $flashcards->count());
        $viewedFlashcards = $flashcards->filter(fn (Flashcard $flashcard): bool => $flashcard->progressRecords->isNotEmpty())->count();

        return min(85, 25 + (int) floor(($viewedFlashcards / $flashcardCount) * 50));
    }

    protected function libraryLessonStatusKey(Lesson $lesson, int $progressPercent): string
    {
        if ($progressPercent >= 100) {
            return 'mastered';
        }

        return $this->progressFromLoadedLesson($lesson) ? 'in_progress' : 'not_started';
    }

    protected function libraryLessonNextStepKey(
        Lesson $lesson,
        ?UserLessonProgress $progress,
        int $flashcardCount,
        int $viewedFlashcards,
    ): string {
        if ($progress?->quiz_passed) {
            return 'training';
        }

        if ((int) $lesson->questions_count > 0 && ($flashcardCount === 0 || $viewedFlashcards >= $flashcardCount)) {
            return 'quiz';
        }

        if ($progress) {
            return 'flashcards';
        }

        return 'lesson';
    }

    protected function libraryLessonNextAction(Lesson $lesson, string $nextStepKey): array
    {
        return match ($nextStepKey) {
            'flashcards' => [
                'label' => 'review',
                'url' => route('lessons.show', $lesson).'#flashcards',
            ],
            'quiz' => [
                'label' => 'unlock_training',
                'url' => route('lessons.show', $lesson).'#mini-quiz',
            ],
            'training' => [
                'label' => 'train',
                'url' => $this->trainingUrlForLesson($lesson),
            ],
            default => [
                'label' => 'open',
                'url' => route('lessons.show', $lesson),
            ],
        };
    }

    protected function recommendationFor(User $user, EloquentCollection $domains): ?array
    {
        $lessons = $domains
            ->flatMap(fn (LearningDomain $domain) => $domain->topics)
            ->flatMap(fn (LearningTopic $topic) => $topic->lessons)
            ->values();

        if ($lessons->isEmpty()) {
            return null;
        }

        $recommendedLesson = $lessons->first(fn (Lesson $lesson): bool => $lesson->getAttribute('library_active_quest') !== null && $lesson->getAttribute('library_status_key') !== 'mastered');
        $reason = 'active_quest';

        if (! $recommendedLesson) {
            $mistakeCategoryIds = $this->recentMistakeCategoryIds($user);
            $recommendedLesson = $mistakeCategoryIds
                ->map(fn (int $categoryId) => $lessons->first(fn (Lesson $lesson): bool => $lesson->learning_category_id === $categoryId && $lesson->getAttribute('library_status_key') !== 'mastered'))
                ->filter()
                ->first();
            $reason = 'recent_errors';
        }

        if (! $recommendedLesson) {
            $recommendedLesson = $lessons
                ->filter(fn (Lesson $lesson): bool => $lesson->getAttribute('library_status_key') !== 'mastered')
                ->sortBy(fn (Lesson $lesson): int => $lesson->getAttribute('library_progress_percent'))
                ->first();
            $reason = 'weak_topic';
        }

        $recommendedLesson ??= $lessons
            ->sortByDesc(fn (Lesson $lesson): int => $lesson->getAttribute('library_progress_percent'))
            ->first();

        if (! $recommendedLesson) {
            return null;
        }

        return [
            'lesson' => $recommendedLesson,
            'topic' => $recommendedLesson->learningTopic,
            'reason' => __("lessons.library.recommendation.reasons.{$reason}"),
            'title' => $recommendedLesson->title,
            'topic_title' => $recommendedLesson->learningTopic?->title,
            'domain_name' => $recommendedLesson->learningDomain?->name,
            'progress' => $recommendedLesson->getAttribute('library_progress_percent'),
            'mastery' => $recommendedLesson->getAttribute('library_mastery_label'),
            'next_step_title' => $recommendedLesson->getAttribute('library_next_step_title'),
            'next_step_body' => $recommendedLesson->getAttribute('library_next_step_body'),
            'next_action_label' => $recommendedLesson->getAttribute('library_next_action_label'),
            'next_action_url' => $recommendedLesson->getAttribute('library_next_action_url'),
            'flashcards_count' => $recommendedLesson->getAttribute('library_flashcards_count'),
            'questions_count' => $recommendedLesson->getAttribute('library_questions_count'),
            'lesson_url' => route('lessons.show', $recommendedLesson),
            'flashcards_url' => route('lessons.show', $recommendedLesson).'#flashcards',
            'training_locked' => $recommendedLesson->getAttribute('library_training_locked'),
            'training_url' => $recommendedLesson->getAttribute('library_training_locked')
                ? route('lessons.show', $recommendedLesson).'#mini-quiz'
                : $this->trainingUrlForLesson($recommendedLesson),
        ];
    }

    protected function recentMistakeCategoryIds(User $user): Collection
    {
        return TrainingAnswer::query()
            ->where('is_correct', false)
            ->whereHas('trainingSession', fn ($query) => $query->where('user_id', $user->id))
            ->with('question.learningCategory')
            ->latest('id')
            ->limit(25)
            ->get()
            ->pluck('question.learning_category_id')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->map(fn (int|string $categoryId): int => (int) $categoryId);
    }

    protected function questMatchesLesson(Quest $quest, Lesson $lesson): bool
    {
        $payload = $quest->objective_payload ?? [];

        if (isset($payload['lesson_id'])) {
            return (int) $payload['lesson_id'] === $lesson->id;
        }

        if (isset($payload['learning_category_id'])) {
            return (int) $payload['learning_category_id'] === $lesson->learning_category_id;
        }

        if (isset($payload['learning_domain_id'])) {
            return (int) $payload['learning_domain_id'] === $lesson->learning_domain_id;
        }

        return $quest->objective_type === QuestObjectiveType::CompleteLessons
            && $quest->learning_domain_id === $lesson->learning_domain_id;
    }

    protected function requiredLessonFromProgress(Collection $lessons, Collection $passedLessonIds): ?Lesson
    {
        if ($lessons->isEmpty()) {
            return null;
        }

        $hasPassedUnlockLesson = $lessons->contains(
            fn (Lesson $lesson): bool => $passedLessonIds->containsStrict((int) $lesson->id),
        );

        return $hasPassedUnlockLesson ? null : $lessons->first();
    }

    protected function trainingUnlockLessons(LearningDomain $domain, LearningCategory $category)
    {
        return Lesson::query()
            ->published()
            ->where('unlocks_training', true)
            ->where('learning_domain_id', $domain->id)
            ->where('learning_category_id', $category->id)
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    protected function domainTrainingUnlockLessons(LearningDomain $domain)
    {
        return Lesson::query()
            ->published()
            ->where('unlocks_training', true)
            ->where('learning_domain_id', $domain->id)
            ->whereNull('learning_category_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    protected function trainingUrlForLesson(Lesson $lesson): string
    {
        return $lesson->learning_category_id
            ? route('training.create', [
                'learning_domain_id' => $lesson->learning_domain_id,
                'learning_category_id' => $lesson->learning_category_id,
            ])
            : route('training.create');
    }

    protected function assertPublished(Lesson $lesson): void
    {
        if ($lesson->status !== LessonStatus::Published) {
            throw new InvalidArgumentException('Ce module n’est pas disponible.');
        }
    }

    protected function decorateLessonFlashcards(User $user, Lesson $lesson): void
    {
        $flashcardIds = $lesson->flashcards->pluck('id');

        $masteryByFlashcard = UserLearningItemProgress::query()
            ->where('user_id', $user->id)
            ->where('item_type', LearningProgressService::ITEM_FLASHCARD)
            ->whereIn('item_key', $flashcardIds->map(fn (int $id): string => (string) $id))
            ->get()
            ->keyBy('item_key');

        $lesson->flashcards->each(function (Flashcard $flashcard, int $index) use ($masteryByFlashcard): void {
            $mastery = $masteryByFlashcard->get((string) $flashcard->id);

            $flashcard->setAttribute('deck_index', $index + 1);
            $flashcard->setAttribute('deck_status_key', $mastery?->mastery_status?->value ?? MasteryStatus::Unknown->value);
            $flashcard->setAttribute('deck_status_label', __("lessons.mastery.status.{$flashcard->getAttribute('deck_status_key')}"));
            $flashcard->setAttribute('deck_mastery_score', $mastery?->mastery_score ?? 0);
        });
    }
}
