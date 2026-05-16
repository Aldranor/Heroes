<?php

namespace Tests\Unit\Services;

use App\Enums\QuestionType;
use App\Enums\TrainingMode;
use App\Models\Learning\Question;
use App\Models\World\UserAdventureNodeProgress;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_perfect_training_session_grants_expected_rewards(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);

        collect(range(1, 5))->each(fn () => $this->createQuestion($domain, $category));

        $trainingService = app(TrainingService::class);
        $session = $trainingService->startSession($user, $domain, $category, $userCompanion);

        foreach (range(1, 5) as $_) {
            $question = $trainingService->currentQuestion($session->refresh());

            $trainingService->submitCurrentAnswer(
                $session,
                $question->answers()->where('is_correct', true)->firstOrFail()->id,
            );
        }

        $session = $trainingService->completeSession($session->refresh());

        $this->assertTrue($session->perfect_session);
        $this->assertSame(5, $session->correct_answers);
        $this->assertSame(70, $session->xp_earned);
        $this->assertSame(35, $session->coins_earned);
        $this->assertSame(70, $user->fresh()->xp);
        $this->assertSame(35, $user->fresh()->coins);
        $this->assertSame(75, $userCompanion->fresh()->xp);
    }

    public function test_training_session_with_errors_grants_expected_rewards(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $otherCategory = $this->createCategory($domain);
        $companion = $this->createCompanion($otherCategory);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);

        collect(range(1, 5))->each(fn () => $this->createQuestion($domain, $category));

        $trainingService = app(TrainingService::class);
        $session = $trainingService->startSession($user, $domain, $category, $userCompanion);

        foreach ([true, true, true, false, false] as $shouldAnswerCorrectly) {
            $question = $trainingService->currentQuestion($session->refresh());
            $answer = $question->answers()
                ->where('is_correct', $shouldAnswerCorrectly)
                ->firstOrFail();

            $trainingService->submitCurrentAnswer($session, $answer->id);
        }

        $session = $trainingService->completeSession($session->refresh());

        $this->assertFalse($session->perfect_session);
        $this->assertSame(3, $session->correct_answers);
        $this->assertSame(36, $session->xp_earned);
        $this->assertSame(25, $session->coins_earned);
        $this->assertSame(36, $user->fresh()->xp);
        $this->assertSame(25, $user->fresh()->coins);
        $this->assertSame(34, $userCompanion->fresh()->xp);
    }

    public function test_training_session_can_target_a_specific_mode(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);

        collect(range(1, 5))->each(fn () => $this->createQuestion($domain, $category, [
            'type' => QuestionType::VisualRecognition,
        ]));
        collect(range(1, 3))->each(fn () => $this->createQuestion($domain, $category, [
            'type' => QuestionType::Definition,
        ]));

        $session = app(TrainingService::class)->startSession(
            $user,
            $domain,
            $category,
            $userCompanion,
            TrainingService::SESSION_QUESTION_COUNT,
            TrainingMode::VisualRecognition,
        );

        $this->assertSame('visual_recognition', data_get($session->metadata, 'mode'));
        $this->assertSame(
            ['visual_recognition'],
            Question::query()->whereIn('id', $session->question_ids)->get()->pluck('type')->map->value->unique()->values()->all(),
        );
    }

    public function test_completing_a_mission_node_unlocks_the_next_node(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $secondCategory = $this->createCategory($domain);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);
        $world = $this->createWorld($domain);
        $map = $this->createAdventureMap($world);
        $firstNode = $this->createAdventureNode($map, [
            'learning_category_id' => $category->id,
            'title' => 'Premier combat',
            'sort_order' => 1,
            'is_start' => true,
        ]);
        $secondNode = $this->createAdventureNode($map, [
            'learning_category_id' => $secondCategory->id,
            'title' => 'Boss de zone',
            'node_type' => 'boss',
            'sort_order' => 2,
        ]);
        $this->createAdventurePath($map, $firstNode, $secondNode);

        collect(range(1, 5))->each(fn () => $this->createQuestion($domain, $category));

        $trainingService = app(TrainingService::class);
        $session = $trainingService->startSession(
            $user,
            $domain,
            $category,
            $userCompanion,
            TrainingService::SESSION_QUESTION_COUNT,
            TrainingMode::Mixed,
            $firstNode,
        );

        foreach (range(1, 5) as $_) {
            $question = $trainingService->currentQuestion($session->refresh());
            $trainingService->submitCurrentAnswer(
                $session,
                $question->answers()->where('is_correct', true)->firstOrFail()->id,
            );
        }

        $trainingService->completeSession($session->refresh());

        $this->assertSame(
            'completed',
            UserAdventureNodeProgress::query()
                ->where('user_id', $user->id)
                ->where('adventure_node_id', $firstNode->id)
                ->value('status')?->value,
        );
        $this->assertSame(
            'available',
            UserAdventureNodeProgress::query()
                ->where('user_id', $user->id)
                ->where('adventure_node_id', $secondNode->id)
                ->value('status')?->value,
        );
    }
}
