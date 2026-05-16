<?php

namespace Tests\Unit\Services;

use App\Enums\QuestObjectiveType;
use App\Models\Lessons\UserLessonProgress;
use App\Services\LessonService;
use App\Services\QuestService;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LessonServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_successful_lesson_quiz_grants_xp_and_unlocks_training(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $questions = collect(range(1, 2))
            ->map(fn () => $this->createQuestion($domain, $category))
            ->all();
        $lesson = $this->createLesson($domain, $category, ['academic_xp_reward' => 120], $questions);
        $user = $this->createUser();

        $lessonService = app(LessonService::class);
        $answers = collect($questions)->mapWithKeys(fn ($question): array => [
            $question->id => $question->answers()->where('is_correct', true)->firstOrFail()->id,
        ])->all();

        $progress = $lessonService->submitQuiz($user, $lesson, $answers);

        $this->assertTrue($progress->quiz_passed);
        $this->assertNotNull($progress->training_unlocked_at);
        $this->assertSame(120, $progress->academic_xp_awarded);
        $this->assertSame(20, $user->fresh()->xp);
        $this->assertSame(2, $user->fresh()->level);
        $this->assertTrue($lessonService->trainingUnlockedFor($user, $domain, $category));
    }

    public function test_library_data_groups_lessons_by_domain_topic_and_recommends_active_quest(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $topic = $this->createTopic($domain, ['title' => 'Priorités']);
        $question = $this->createQuestion($domain, $category);
        $lesson = $this->createLesson($domain, $category, ['learning_topic' => $topic], [$question]);
        $quest = $this->createQuest([
            'learning_domain_id' => $domain->id,
            'objective_type' => QuestObjectiveType::CompleteLessons,
            'objective_target' => 1,
            'objective_payload' => ['lesson_id' => $lesson->id],
        ]);
        $user = $this->createUser();

        app(QuestService::class)->acceptQuest($user, $quest);

        $data = app(LessonService::class)->libraryData($user);
        $decoratedLesson = $data['visibleTopics']->first()->lessons->first();

        $this->assertSame($domain->id, $data['selectedDomain']->id);
        $this->assertSame($topic->id, $data['visibleTopics']->first()->id);
        $this->assertSame($lesson->id, $data['recommendation']['lesson']->id);
        $this->assertSame('lesson', $decoratedLesson->getAttribute('library_next_step_key'));
        $this->assertSame(route('lessons.show', $lesson), $decoratedLesson->getAttribute('library_next_action_url'));
        $this->assertSame(2, $decoratedLesson->getAttribute('library_flashcards_count'));
    }

    public function test_failed_lesson_quiz_does_not_unlock_training(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $question = $this->createQuestion($domain, $category);
        $lesson = $this->createLesson($domain, $category, [], [$question]);
        $user = $this->createUser();

        $lessonService = app(LessonService::class);
        $wrongAnswer = $question->answers()->where('is_correct', false)->firstOrFail();

        $progress = $lessonService->submitQuiz($user, $lesson, [
            $question->id => $wrongAnswer->id,
        ]);

        $this->assertFalse($progress->quiz_passed);
        $this->assertNull($progress->training_unlocked_at);
        $this->assertSame(0, $progress->academic_xp_awarded);
        $this->assertFalse($lessonService->trainingUnlockedFor($user, $domain, $category));
    }

    public function test_failed_retry_does_not_lock_an_already_completed_lesson(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $question = $this->createQuestion($domain, $category);
        $lesson = $this->createLesson($domain, $category, [], [$question]);
        $user = $this->createUser();
        $lessonService = app(LessonService::class);

        $lessonService->submitQuiz($user, $lesson, [
            $question->id => $question->answers()->where('is_correct', true)->firstOrFail()->id,
        ]);

        $progress = $lessonService->submitQuiz($user, $lesson, [
            $question->id => $question->answers()->where('is_correct', false)->firstOrFail()->id,
        ]);

        $this->assertTrue($progress->quiz_passed);
        $this->assertNotNull($progress->training_unlocked_at);
        $this->assertTrue($lessonService->trainingUnlockedFor($user, $domain, $category));
        $this->assertSame(20, $user->fresh()->xp);
        $this->assertSame(2, $user->fresh()->level);
    }

    public function test_training_requires_required_lesson_when_category_has_course(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);
        $questions = collect(range(1, 5))
            ->map(fn () => $this->createQuestion($domain, $category))
            ->all();

        $this->createLesson($domain, $category, [], [$questions[0]]);

        $this->expectException(InvalidArgumentException::class);

        app(TrainingService::class)->startSession($user, $domain, $category, $userCompanion);
    }

    public function test_completing_lesson_updates_matching_course_quest(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $question = $this->createQuestion($domain, $category);
        $lesson = $this->createLesson($domain, $category, [], [$question]);
        $quest = $this->createQuest([
            'objective_type' => QuestObjectiveType::CompleteLessons,
            'objective_target' => 1,
            'objective_payload' => ['lesson_id' => $lesson->id],
        ]);
        $user = $this->createUser();
        $userQuest = app(QuestService::class)->acceptQuest($user, $quest);

        app(LessonService::class)->submitQuiz($user, $lesson, [
            $question->id => $question->answers()->where('is_correct', true)->firstOrFail()->id,
        ]);

        $this->assertSame(1, $userQuest->fresh()->progress);
        $this->assertTrue(UserLessonProgress::query()->where('user_id', $user->id)->where('lesson_id', $lesson->id)->where('quiz_passed', true)->exists());
    }
}
