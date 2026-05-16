<?php

namespace Tests\Feature;

use App\Models\Avatar\Avatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Services\CreatesGameData;

class MissionMapFlowTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_user_can_open_the_mission_map_module_from_the_hub_flow(): void
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
        $this->createAdventureDialogue($map, [
            'title' => 'Briefing',
            'script' => [
                ['speaker' => 'npc', 'name' => 'Capitaine Lys', 'text' => 'La map s’ouvre. Nettoie le sentier.'],
                ['speaker' => 'hero', 'text' => 'Je prends la route.'],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('missions.index', ['world' => $world->id, 'map' => $map->id, 'node' => $node->id]))
            ->assertOk()
            ->assertSee('Carte des missions')
            ->assertSee('Forêt des signes')
            ->assertSee('Sentier du gardien')
            ->assertSee('Capitaine Lys')
            ->assertSee('Avant-poste');
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
