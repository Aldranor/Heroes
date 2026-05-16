<?php

namespace Tests\Feature;

use App\Models\Avatar\Avatar;
use App\Models\Training\TrainingSession;
use App\Services\TrainingService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Services\CreatesGameData;

class TrainingFlowTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_user_can_complete_a_training_quiz_flow(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);
        $this->createAvatar($user);

        collect(range(1, 5))->each(fn () => $this->createQuestion($domain, $category));

        $this->actingAs($user)
            ->get(route('training.create'))
            ->assertOk()
            ->assertSee('Prépare une session rapide');

        $this->actingAs($user)
            ->post(route('training.store'), [
                'learning_domain_id' => $domain->id,
                'learning_category_id' => $category->id,
                'user_companion_id' => $userCompanion->id,
            ])
            ->assertRedirect();

        $session = TrainingSession::query()->where('user_id', $user->id)->firstOrFail();
        $trainingService = app(TrainingService::class);

        foreach (range(1, 5) as $_) {
            $question = $trainingService->currentQuestion($session->refresh());
            $answer = $question->answers()->where('is_correct', true)->firstOrFail();

            $this->actingAs($user)
                ->post(route('training.answer', $session), ['answer_id' => $answer->id])
                ->assertRedirect(route('training.show', [
                    'trainingSession' => $session,
                    'feedback' => $session->answers()->latest('id')->value('id'),
                ]));
        }

        $this->actingAs($user)
            ->get(route('training.show', $session))
            ->assertOk()
            ->assertSee('Voir le résultat');

        $this->actingAs($user)
            ->get(route('training.result', $session))
            ->assertOk()
            ->assertSee('Mission complétée')
            ->assertSee('5/5');

        $this->assertSame(70, $user->fresh()->xp);
        $this->assertSame(35, $user->fresh()->coins);
        $this->assertSame(75, $userCompanion->fresh()->xp);
    }

    private function createAvatar($user): Avatar
    {
        return Avatar::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Trainer',
            'style' => 'strategist',
            'colors' => [
                'hair' => '#1E1E1E',
                'skin' => '#F7D7C4',
                'accent' => '#5B6CFF',
            ],
            'equipped_items' => [
                'body' => config('avatar.defaults.body'),
                'hair' => config('avatar.defaults.hair'),
                'outfit_preset' => 'strategist',
                'equipment' => [],
            ],
        ]);
    }
}
