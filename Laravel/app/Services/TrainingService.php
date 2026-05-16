<?php

namespace App\Services;

use App\Enums\QuestionStatus;
use App\Enums\QuestObjectiveType;
use App\Enums\TrainingMode;
use App\Enums\TrainingSessionStatus;
use App\Models\Companions\UserCompanion;
use App\Models\Learning\Answer;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\Question;
use App\Models\Lessons\Lesson;
use App\Models\Training\TrainingAnswer;
use App\Models\Training\TrainingSession;
use App\Models\User;
use App\Models\World\AdventureNode;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TrainingService
{
    public const SESSION_QUESTION_COUNT = 5;

    public function __construct(
        protected XpService $xpService,
        protected RewardService $rewardService,
        protected LessonService $lessonService,
        protected QuestService $questService,
        protected LearningProgressService $learningProgressService,
        protected QuestionPresentationService $questionPresentationService,
        protected AdventureMapService $adventureMapService,
    ) {}

    public function selectionOptions(
        User $user,
        ?int $selectedWorldId = null,
        ?int $selectedMapId = null,
        ?int $selectedNodeId = null,
    ): array
    {
        $domains = LearningDomain::query()
            ->where('is_active', true)
            ->whereHas('questions', fn ($query) => $query->where('status', QuestionStatus::Published))
            ->with(['categories' => function ($query) {
                $query
                    ->whereHas('questions', fn ($questionQuery) => $questionQuery->where('status', QuestionStatus::Published))
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->pipe(fn ($domains) => $this->lessonService->decorateCategoriesForTraining($user, $domains));

        $categoryProgress = $this->learningProgressService->categoryProgressMap(
            $user,
            $domains->flatMap(fn (LearningDomain $domain) => $domain->categories->pluck('id'))->all(),
        );

        $domains->each(function (LearningDomain $domain) use ($categoryProgress): void {
            $domain->categories->each(function (LearningCategory $category) use ($categoryProgress): void {
                $mastery = $categoryProgress->get($category->id);

                $category->setAttribute('mastery_key', $mastery?->mastery_status?->value ?? 'unknown');
                $category->setAttribute('mastery_label', __("lessons.mastery.status.{$category->getAttribute('mastery_key')}"));
                $category->setAttribute('mastery_score', $mastery?->mastery_score ?? 0);
            });
        });

        $missionBoard = $this->adventureMapService->missionBoard($user, $domains, $selectedWorldId, $selectedMapId, $selectedNodeId);
        $selectedNode = $missionBoard['selectedNode'];
        $selectedDomain = $selectedNode?->map?->world?->learningDomain;
        $selectedCategory = $selectedNode?->learningCategory;

        return [
            'domains' => $domains,
            'companions' => $user->userCompanions()
                ->with(['companion.specializationCategory'])
                ->orderByDesc('is_favorite')
                ->get(),
            'modes' => $this->trainingModes(),
            'recommendation' => $this->selectionRecommendation($user, $domains),
            'missionWorlds' => $missionBoard['worlds'],
            'selectedWorld' => $missionBoard['selectedWorld'],
            'selectedMap' => $missionBoard['selectedMap'],
            'selectedNode' => $selectedNode,
            'selectedDialogue' => $missionBoard['selectedDialogue'],
            'selectedMissionDomainId' => $selectedDomain?->id,
            'selectedMissionCategoryId' => $selectedCategory?->id,
        ];
    }

    public function startSessionFromSelection(User $user, array $selection): TrainingSession
    {
        $adventureNode = $this->adventureMapService->nodeForSelection((int) ($selection['adventure_node_id'] ?? 0));
        $learningDomain = $adventureNode?->map?->world?->learningDomain
            ?? LearningDomain::query()->findOrFail((int) $selection['learning_domain_id']);
        $learningCategory = $adventureNode?->learningCategory
            ?? LearningCategory::query()->findOrFail((int) $selection['learning_category_id']);
        $userCompanion = UserCompanion::query()
            ->with('companion.specializationCategory')
            ->findOrFail((int) $selection['user_companion_id']);
        $mode = TrainingMode::tryFrom((string) ($selection['mode'] ?? TrainingMode::Mixed->value)) ?? TrainingMode::Mixed;

        if ($adventureNode) {
            $this->adventureMapService->assertNodeAvailableForUser($user, $adventureNode);
        }

        return $this->startSession($user, $learningDomain, $learningCategory, $userCompanion, self::SESSION_QUESTION_COUNT, $mode, $adventureNode);
    }

    public function startSession(
        User $user,
        LearningDomain $learningDomain,
        LearningCategory $learningCategory,
        UserCompanion $userCompanion,
        int $totalQuestions = self::SESSION_QUESTION_COUNT,
        ?TrainingMode $mode = null,
        ?AdventureNode $adventureNode = null,
    ): TrainingSession {
        if ($totalQuestions !== self::SESSION_QUESTION_COUNT) {
            throw new InvalidArgumentException('A training session must contain exactly 5 questions.');
        }

        if ($learningCategory->learning_domain_id !== $learningDomain->id) {
            throw new InvalidArgumentException('The selected category does not belong to the provided domain.');
        }

        if ($userCompanion->user_id !== $user->id) {
            throw new InvalidArgumentException('The selected companion does not belong to the player.');
        }

        $requiredLesson = $this->lessonService->requiredLessonForTraining($user, $learningDomain, $learningCategory);

        if ($requiredLesson) {
            throw new InvalidArgumentException("Cette mission est verrouillée. Réussissez d’abord le module « {$requiredLesson->title} ».");
        }

        $mode ??= TrainingMode::Mixed;
        $questions = Question::query()
            ->where('learning_domain_id', $learningDomain->id)
            ->where('learning_category_id', $learningCategory->id)
            ->where('status', QuestionStatus::Published)
            ->with('answers')
            ->get();

        $questionIds = $this->selectQuestionIds($user, $questions, $learningCategory, $totalQuestions, $mode);

        if (count($questionIds) < $totalQuestions) {
            throw new InvalidArgumentException('Not enough published questions are available for this session.');
        }

        $trainingSession = TrainingSession::query()->create([
            'user_id' => $user->id,
            'learning_domain_id' => $learningDomain->id,
            'learning_category_id' => $learningCategory->id,
            'user_companion_id' => $userCompanion->id,
            'adventure_node_id' => $adventureNode?->id,
            'status' => TrainingSessionStatus::Pending,
            'question_ids' => $questionIds,
            'metadata' => [
                'mode' => $mode->value,
                'skills' => $this->defaultSkillCharges(),
                'mission' => $adventureNode ? [
                    'node_title' => $adventureNode->title,
                    'node_type' => $adventureNode->node_type,
                ] : null,
            ],
            'total_questions' => $totalQuestions,
            'started_at' => now(),
        ]);

        if ($adventureNode) {
            $this->adventureMapService->markMissionStarted($user, $adventureNode, $trainingSession);
        }

        return $trainingSession;
    }

    public function currentQuestion(TrainingSession $trainingSession): ?Question
    {
        $trainingSession->loadMissing('answers');
        $answeredQuestionIds = $trainingSession->answers->pluck('question_id')->all();
        $questionId = collect($trainingSession->question_ids ?? [])
            ->first(fn ($id): bool => ! in_array((int) $id, $answeredQuestionIds, true));

        if (! $questionId) {
            return null;
        }

        $question = Question::query()
            ->with(['answers', 'learningCategory'])
            ->findOrFail($questionId);

        return $this->questionPresentationService->decorate($question);
    }

    public function answeredCount(TrainingSession $trainingSession): int
    {
        if ($trainingSession->relationLoaded('answers')) {
            return $trainingSession->answers->count();
        }

        return $trainingSession->answers()->count();
    }

    public function readyForResult(TrainingSession $trainingSession): bool
    {
        return $this->answeredCount($trainingSession) === $trainingSession->total_questions;
    }

    public function submitCurrentAnswer(
        TrainingSession $trainingSession,
        int $answerId,
        ?int $timeSpentSeconds = null,
        array $skillRequests = [],
    ): TrainingAnswer {
        return DB::transaction(function () use ($trainingSession, $answerId, $timeSpentSeconds, $skillRequests) {
            $lockedSession = TrainingSession::query()
                ->with(['user', 'userCompanion.companion.specializationCategory'])
                ->lockForUpdate()
                ->findOrFail($trainingSession->id);

            if ($lockedSession->status !== TrainingSessionStatus::Pending) {
                throw new InvalidArgumentException('Only pending training sessions can accept answers.');
            }

            $question = $this->currentQuestion($lockedSession);

            if (! $question) {
                throw new InvalidArgumentException('This training session has no unanswered question.');
            }

            $answer = Answer::query()->findOrFail($answerId);

            if ($answer->question_id !== $question->id) {
                throw new InvalidArgumentException('The selected answer does not belong to the current question.');
            }

            [$sessionMetadata, $usedSkills] = $this->consumeSkillRequests($lockedSession->metadata ?? [], $skillRequests);

            $isCorrect = $answer->is_correct;
            $userXp = $this->xpService->calculateUserAnswerXp($isCorrect);

            if (($usedSkills['bonus_xp'] ?? false) && $isCorrect) {
                $userXp += 10;
            }

            $companionXp = $this->xpService->calculateCompanionAnswerXp(
                $lockedSession->userCompanion,
                $question->learningCategory,
                $isCorrect,
            );

            $trainingAnswer = TrainingAnswer::query()->create([
                'training_session_id' => $lockedSession->id,
                'question_id' => $question->id,
                'answer_id' => $answer->id,
                'is_correct' => $isCorrect,
                'time_spent_seconds' => $timeSpentSeconds,
                'awarded_user_xp' => $userXp,
                'awarded_creature_xp' => 0,
                'awarded_companion_xp' => $companionXp,
                'metadata' => [
                    'skills_used' => array_keys(array_filter($usedSkills)),
                    'question_type' => $question->type->value,
                ],
            ]);

            $this->rewardService->grantUserXp($lockedSession->user, $userXp);
            $this->xpService->grantCompanionXp($lockedSession->userCompanion, $companionXp);
            $this->learningProgressService->registerQuestionResult(
                $lockedSession->user,
                $question,
                $isCorrect,
                $timeSpentSeconds,
            );

            $lockedSession->forceFill([
                'correct_answers' => $lockedSession->correct_answers + ($isCorrect ? 1 : 0),
                'xp_earned' => $lockedSession->xp_earned + $userXp,
                'metadata' => $sessionMetadata,
            ])->save();

            return $trainingAnswer->refresh();
        });
    }

    public function feedbackForAnswer(TrainingSession $trainingSession, ?int $trainingAnswerId): ?array
    {
        if (! $trainingAnswerId) {
            return null;
        }

        $trainingAnswer = TrainingAnswer::query()
            ->with(['question.learningCategory', 'question.correctAnswer', 'question.answers', 'answer', 'trainingSession.user'])
            ->where('training_session_id', $trainingSession->id)
            ->find($trainingAnswerId);

        if (! $trainingAnswer) {
            return null;
        }

        $question = $this->questionPresentationService->decorate($trainingAnswer->question);

        return [
            'answer' => $trainingAnswer,
            'question' => $question,
            'correctAnswer' => $question->correctAnswer,
            'explanation' => $question->explanation,
            'revision' => $this->revisionPlan($trainingAnswer->trainingSession->user, $question),
        ];
    }

    public function completeSession(TrainingSession $trainingSession): TrainingSession
    {
        if ($trainingSession->status === TrainingSessionStatus::Completed) {
            return $trainingSession->refresh();
        }

        if (! $this->readyForResult($trainingSession)) {
            throw new InvalidArgumentException('The training session cannot be completed until all questions are answered.');
        }

        return DB::transaction(function () use ($trainingSession) {
            $lockedSession = TrainingSession::query()
                ->with(['user', 'adventureNode'])
                ->lockForUpdate()
                ->findOrFail($trainingSession->id);

            $answers = $lockedSession->answers()->get();
            $correctAnswers = $answers->where('is_correct', true)->count();
            $perfectSession = $correctAnswers === $lockedSession->total_questions;
            $answerXp = (int) $answers->sum('awarded_user_xp');
            $perfectBonus = $this->xpService->calculateUserTrainingCompletionBonus($correctAnswers, $lockedSession->total_questions);
            $coinsEarned = $this->calculateCoinsEarned($correctAnswers);

            if ($perfectBonus > 0) {
                $this->rewardService->grantUserXp($lockedSession->user, $perfectBonus);
            }

            $this->rewardService->grantCoins($lockedSession->user, $coinsEarned);

            $lockedSession->update([
                'status' => TrainingSessionStatus::Completed,
                'correct_answers' => $correctAnswers,
                'xp_earned' => $answerXp + $perfectBonus,
                'coins_earned' => $coinsEarned,
                'perfect_session' => $perfectSession,
                'completed_at' => now(),
            ]);

            $this->questService->updateProgress($lockedSession->user, QuestObjectiveType::CompleteTrainingSessions, 1, [
                'learning_domain_id' => $lockedSession->learning_domain_id,
                'learning_category_id' => $lockedSession->learning_category_id,
            ]);
            $this->questService->updateProgress($lockedSession->user, QuestObjectiveType::AnswerCorrectQuestions, $correctAnswers, [
                'learning_domain_id' => $lockedSession->learning_domain_id,
                'learning_category_id' => $lockedSession->learning_category_id,
            ]);

            if ($lockedSession->adventureNode) {
                $this->adventureMapService->markMissionCompleted($lockedSession->user, $lockedSession->adventureNode, $lockedSession);
            }

            return $lockedSession->refresh();
        });
    }

    public function reviewAnswers(TrainingSession $trainingSession): EloquentCollection
    {
        return $trainingSession->answers()
            ->with(['question.learningCategory', 'question.correctAnswer', 'question.answers', 'answer', 'trainingSession.user'])
            ->orderBy('id')
            ->get()
            ->each(function (TrainingAnswer $trainingAnswer): void {
                $trainingAnswer->setRelation('question', $this->questionPresentationService->decorate($trainingAnswer->question));
                $trainingAnswer->setAttribute('revision', $this->revisionPlan($trainingAnswer->trainingSession->user, $trainingAnswer->question));
            });
    }

    protected function calculateCoinsEarned(int $correctAnswers): int
    {
        return 10 + ($correctAnswers * 5);
    }

    protected function defaultSkillCharges(): array
    {
        return [
            'hint' => 1,
            'extra_time' => 1,
            'remove_choice' => 1,
            'bonus_xp' => 1,
        ];
    }

    protected function trainingModes(): Collection
    {
        return collect([
            ['key' => TrainingMode::Mixed->value, 'label' => 'Mixte', 'body' => 'Mele plusieurs formats pour automatiser les reflexes.'],
            ['key' => TrainingMode::VisualRecognition->value, 'label' => 'Reconnaissance', 'body' => 'Image ou repere visuel vers la bonne reponse.'],
            ['key' => TrainingMode::Association->value, 'label' => 'Association', 'body' => 'Classe un element dans la bonne categorie.'],
            ['key' => TrainingMode::Definition->value, 'label' => 'Definition', 'body' => 'Relie une definition au bon item.'],
            ['key' => TrainingMode::Situation->value, 'label' => 'Situation', 'body' => 'Choisis la bonne action dans un contexte.'],
            ['key' => TrainingMode::ReverseLookup->value, 'label' => 'Reverse', 'body' => 'Texte ou indice vers le bon visuel.'],
            ['key' => TrainingMode::Traps->value, 'label' => 'Pieges', 'body' => 'Travaille les distracteurs proches et les confusions frequentes.'],
        ]);
    }

    protected function selectionRecommendation(User $user, EloquentCollection $domains): ?array
    {
        $recentWrongAnswer = TrainingAnswer::query()
            ->where('is_correct', false)
            ->whereHas('trainingSession', fn ($query) => $query->where('user_id', $user->id))
            ->with('question')
            ->latest('id')
            ->first();

        if ($recentWrongAnswer && $recentWrongAnswer->question) {
            return [
                'reason' => 'Erreurs recentes',
                'domain_id' => $recentWrongAnswer->question->learning_domain_id,
                'category_id' => $recentWrongAnswer->question->learning_category_id,
                'mode' => $recentWrongAnswer->question->type->value,
                'lesson_url' => $this->revisionPlan($user, $recentWrongAnswer->question)['lesson_url'] ?? null,
            ];
        }

        $weakCategory = $domains
            ->flatMap(fn (LearningDomain $domain) => $domain->categories->map(fn (LearningCategory $category) => [$domain, $category]))
            ->sortBy(fn (array $pair): int => $pair[1]->getAttribute('mastery_score'))
            ->first();

        if (! $weakCategory) {
            return null;
        }

        return [
            'reason' => 'Categorie la plus fragile',
            'domain_id' => $weakCategory[0]->id,
            'category_id' => $weakCategory[1]->id,
            'mode' => TrainingMode::Mixed->value,
            'lesson_url' => null,
        ];
    }

    protected function selectQuestionIds(
        User $user,
        EloquentCollection $questions,
        LearningCategory $learningCategory,
        int $totalQuestions,
        TrainingMode $mode,
    ): array {
        $filtered = $this->filterQuestionsForMode($questions, $mode);
        $prioritizedIds = TrainingAnswer::query()
            ->where('is_correct', false)
            ->whereHas('trainingSession', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('question', fn ($query) => $query->where('learning_category_id', $learningCategory->id))
            ->latest('id')
            ->limit(12)
            ->pluck('question_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $selected = $filtered
            ->filter(fn (Question $question): bool => in_array($question->id, $prioritizedIds, true))
            ->shuffle()
            ->concat(
                $filtered
                    ->reject(fn (Question $question): bool => in_array($question->id, $prioritizedIds, true))
                    ->shuffle()
            )
            ->unique('id');

        if ($selected->count() < $totalQuestions) {
            $selected = $selected->concat(
                $questions
                    ->reject(fn (Question $question): bool => $selected->contains('id', $question->id))
                    ->shuffle()
            )->unique('id');
        }

        return $selected->take($totalQuestions)->pluck('id')->all();
    }

    protected function filterQuestionsForMode(EloquentCollection $questions, TrainingMode $mode): EloquentCollection
    {
        if ($mode === TrainingMode::Mixed) {
            return $questions->shuffle()->values();
        }

        if ($mode === TrainingMode::Traps) {
            $trapQuestions = $questions
                ->filter(fn (Question $question): bool => (bool) data_get($question->metadata, 'has_traps', false))
                ->values();

            return $trapQuestions->isNotEmpty() ? $trapQuestions : $questions->shuffle()->values();
        }

        $typedQuestions = $questions
            ->filter(fn (Question $question): bool => $question->type->value === $mode->value)
            ->values();

        return $typedQuestions->isNotEmpty() ? $typedQuestions : $questions->shuffle()->values();
    }

    protected function consumeSkillRequests(array $sessionMetadata, array $skillRequests): array
    {
        $sessionMetadata['skills'] = array_merge($this->defaultSkillCharges(), $sessionMetadata['skills'] ?? []);
        $usedSkills = [
            'hint' => false,
            'extra_time' => false,
            'remove_choice' => false,
            'bonus_xp' => false,
        ];

        foreach ($usedSkills as $skill => $used) {
            if (! ($skillRequests[$skill] ?? false)) {
                continue;
            }

            if (($sessionMetadata['skills'][$skill] ?? 0) <= 0) {
                continue;
            }

            $sessionMetadata['skills'][$skill]--;
            $usedSkills[$skill] = true;
        }

        return [$sessionMetadata, $usedSkills];
    }

    protected function revisionPlan(User $user, Question $question): array
    {
        $lesson = Lesson::query()
            ->published()
            ->where('learning_domain_id', $question->learning_domain_id)
            ->where('learning_category_id', $question->learning_category_id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->first();

        $categoryProgress = $this->learningProgressService
            ->categoryProgressMap($user, [$question->learning_category_id])
            ->get($question->learning_category_id);

        return [
            'mastery' => $categoryProgress?->mastery_status?->value ?? 'unknown',
            'lesson_url' => $lesson ? route('lessons.show', $lesson) : null,
            'flashcards_url' => $lesson ? route('lessons.show', $lesson).'#flashcards' : null,
            'training_url' => route('training.create', [
                'learning_domain_id' => $question->learning_domain_id,
                'learning_category_id' => $question->learning_category_id,
                'mode' => $question->type->value,
            ]),
        ];
    }
}
