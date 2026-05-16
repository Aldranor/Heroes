<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\Avatar\Avatar;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Services\CreatesGameData;

class LessonFlowTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_marking_a_flashcard_redirects_back_to_the_requested_card(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $lesson = $this->createLesson($domain, $category);
        $flashcard = $lesson->flashcards()->firstOrFail();
        $user = $this->createUser();
        $this->createAvatar($user);

        $this->actingAs($user)
            ->post(route('lessons.flashcards.update', [$lesson, $flashcard]), [
                'mastered' => '1',
                'card' => 1,
            ])
            ->assertRedirect(route('lessons.show', [
                'lesson' => $lesson,
                'card' => 1,
            ]).'#flashcards');
    }

    public function test_lesson_page_surfaces_a_clear_learning_path_and_flashcard_actions(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $question = $this->createQuestion($domain, $category, [
            'type' => QuestionType::ReverseLookup,
            'metadata' => [],
        ]);
        $lesson = $this->createLesson($domain, $category, [], [$question]);
        $user = $this->createUser();
        $this->createAvatar($user);

        $this->actingAs($user)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('Commencer le cours')
            ->assertSee('À revoir')
            ->assertSee('Voir le verso')
            ->assertSee('À identifier')
            ->assertDontSee('Repere visuel')
            ->assertDontSee('Signalisation');
    }

    public function test_library_page_exposes_the_learning_journey_for_each_module(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $question = $this->createQuestion($domain, $category);
        $this->createLesson($domain, $category, [], [$question]);
        $user = $this->createUser();
        $this->createAvatar($user);

        $this->actingAs($user)
            ->get(route('lessons.index'))
            ->assertOk()
            ->assertSee('Lire la leçon')
            ->assertSee('Réviser les flashcards')
            ->assertSee('Répondre au mini quiz');
    }

    private function createAvatar($user): Avatar
    {
        return Avatar::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Scholar',
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
