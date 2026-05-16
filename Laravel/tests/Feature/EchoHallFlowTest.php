<?php

namespace Tests\Feature;

use App\Models\Avatar\Avatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Services\CreatesGameData;

class EchoHallFlowTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_completed_mission_scene_is_hidden_on_return_and_replayable_in_echo_hall(): void
    {
        $domain = $this->createDomain(['name' => 'Signalisation']);
        $category = $this->createCategory($domain, ['name' => 'Panneaux']);
        $lesson = $this->createLesson($domain, $category, ['title' => 'Leçon Signalisation']);
        $companion = $this->createCompanion($category);
        $user = $this->createUser();
        $this->createAvatar($user);
        $this->createUserCompanion($user, $companion);
        $world = $this->createWorld($domain, ['name' => 'Forêt des signes']);
        $map = $this->createAdventureMap($world, [
            'name' => 'Sentier du gardien',
            'learning_category_id' => $category->id,
            'lesson_id' => $lesson->id,
        ]);
        $node = $this->createAdventureNode($map, [
            'learning_category_id' => $category->id,
            'lesson_id' => $lesson->id,
            'title' => 'Avant-poste',
            'is_start' => true,
        ]);
        $dialogue = $this->createAdventureDialogue($map, [
            'title' => 'Briefing',
            'script' => [
                ['speaker' => 'npc', 'name' => 'Capitaine Lys', 'text' => 'La map s’ouvre. Nettoie le sentier.'],
                ['speaker' => 'hero', 'text' => 'Je prends la route.'],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('story-scenes.complete'), [
                'scene_type' => 'adventure_dialogue',
                'scene_id' => $dialogue->id,
            ])
            ->assertNoContent();

        $this->actingAs($user)
            ->get(route('missions.index', ['world' => $world->id, 'map' => $map->id, 'node' => $node->id]))
            ->assertOk()
            ->assertSee('data-scene-starts-finished="true"', false);

        $this->actingAs($user)
            ->get(route('echoes.index'))
            ->assertOk()
            ->assertSee('Hall des échos')
            ->assertSee('Briefing')
            ->assertSee('Capitaine Lys');
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
