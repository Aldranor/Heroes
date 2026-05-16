<?php

namespace Tests\Feature;

use App\Enums\AdventureNodeProgressStatus;
use App\Models\Avatar\Avatar;
use App\Models\Battle\Battle;
use App\Models\World\UserAdventureNodeProgress;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Services\CreatesGameData;

class CombatFlowTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_user_can_start_a_mission_battle_from_a_node(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $domain = $this->createDomain(['name' => 'Signalisation']);
        $category = $this->createCategory($domain, ['name' => 'Panneaux']);
        $lesson = $this->createLesson($domain, $category, ['title' => 'Leçon Signalisation']);
        $enemy = $this->createEnemy($domain, ['name' => 'Cône frondeur']);
        $companion = $this->createCompanion($category, ['name' => 'Observateur']);
        $user = $this->createUser();
        $this->createAvatar($user);
        $userCompanion = $this->createUserCompanion($user, $companion);
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
            'node_type' => 'combat_normal',
            'is_start' => true,
            'metadata' => ['enemy_id' => $enemy->id, 'enemy_count' => 1],
        ]);

        $this->actingAs($user)
            ->post(route('combat.store'), [
                'adventure_node_id' => $node->id,
                'user_companion_ids' => [$userCompanion->id],
            ])
            ->assertRedirect();

        $battle = Battle::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('combat.show', $battle))
            ->assertOk();

        $enemyCount = collect(data_get($battle->fresh()->state, 'actors', []))
            ->filter(fn (array $actor): bool => ($actor['side'] ?? null) === 'enemy')
            ->count();

        $this->assertGreaterThanOrEqual(1, $enemyCount);
    }

    public function test_winning_a_mission_battle_completes_the_node_and_unlocks_the_next_one(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $lesson = $this->createLesson($domain, $category);
        $enemy = $this->createEnemy($domain, [
            'hp' => 12,
            'attack' => 6,
            'defense' => 2,
            'speed' => 1,
            'reward_xp' => 20,
            'reward_coins' => 12,
        ]);
        $companion = $this->createCompanion($category);
        $user = $this->createUser(['level' => 6]);
        $this->createAvatar($user);
        $userCompanion = $this->createUserCompanion($user, $companion, ['level' => 5]);
        $world = $this->createWorld($domain);
        $map = $this->createAdventureMap($world, [
            'learning_category_id' => $category->id,
            'lesson_id' => $lesson->id,
        ]);
        $firstNode = $this->createAdventureNode($map, [
            'learning_category_id' => $category->id,
            'lesson_id' => $lesson->id,
            'title' => 'Avant-poste',
            'node_type' => 'combat_normal',
            'is_start' => true,
            'metadata' => ['enemy_id' => $enemy->id, 'enemy_count' => 1],
        ]);
        $secondNode = $this->createAdventureNode($map, [
            'learning_category_id' => $category->id,
            'lesson_id' => $lesson->id,
            'title' => 'Boss',
            'node_type' => 'boss',
            'sort_order' => 2,
        ]);
        $this->createAdventurePath($map, $firstNode, $secondNode);

        $this->actingAs($user)
            ->post(route('combat.store'), [
                'adventure_node_id' => $firstNode->id,
                'user_companion_ids' => [$userCompanion->id],
            ])
            ->assertRedirect();

        $battle = Battle::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('combat.action', $battle), [
                'skill' => 'attaque-simple',
            ])
            ->assertRedirect(route('combat.show', $battle));

        $this->assertSame('won', $battle->fresh()->status->value);

        $completedProgress = UserAdventureNodeProgress::query()
            ->where('user_id', $user->id)
            ->where('adventure_node_id', $firstNode->id)
            ->first();
        $unlockedProgress = UserAdventureNodeProgress::query()
            ->where('user_id', $user->id)
            ->where('adventure_node_id', $secondNode->id)
            ->first();

        $this->assertSame(AdventureNodeProgressStatus::Completed, $completedProgress?->status);
        $this->assertSame(AdventureNodeProgressStatus::Available, $unlockedProgress?->status);

        $this->actingAs($user)
            ->get(route('combat.show', $battle))
            ->assertOk()
            ->assertSee('Victoire');
    }

    private function createAvatar($user): Avatar
    {
        return Avatar::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Héros',
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
