<?php

namespace Tests\Unit\Services;

use App\Enums\EnemyType;
use App\Services\BattleService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_calculate_damage_uses_expected_formula(): void
    {
        $damage = app(BattleService::class)->calculateDamage(20, 15, 12);

        $this->assertSame(23, $damage);
    }

    public function test_battle_victory_marks_battle_won_and_claims_rewards(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $skill = $this->createSkill(['power' => 30]);
        $creature = $this->createCreature($domain, $category, ['default_skill' => $skill, 'base_attack' => 20]);
        $creature->skills()->syncWithoutDetaching([
            $skill->id => ['unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
        ]);

        $user = $this->createUser();
        $userCreature = app(RewardService::class)->grantCreature($user, $creature);
        $enemy = $this->createEnemy($domain, ['hp' => 30, 'defense' => 5, 'reward_xp' => 30, 'reward_coins' => 12]);

        $battleService = app(BattleService::class);
        $battle = $battleService->startBattle($user, $enemy, [$userCreature]);
        $battle = $battleService->playerAction($battle, $userCreature, $skill);

        $this->assertSame('won', $battle->fresh()->status->value);
        $this->assertTrue($battle->fresh()->reward_claimed);
        $this->assertSame(30, $user->fresh()->xp);
        $this->assertSame(12, $user->fresh()->coins);
    }

    public function test_boss_reward_adds_bonus_xp(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $skill = $this->createSkill(['power' => 40]);
        $creature = $this->createCreature($domain, $category, ['default_skill' => $skill, 'base_attack' => 22]);
        $creature->skills()->syncWithoutDetaching([
            $skill->id => ['unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
        ]);

        $user = $this->createUser();
        $userCreature = app(RewardService::class)->grantCreature($user, $creature);
        $enemy = $this->createEnemy($domain, [
            'type' => EnemyType::Boss,
            'is_boss' => true,
            'hp' => 30,
            'defense' => 4,
            'reward_xp' => 0,
            'reward_coins' => 0,
        ]);

        $battleService = app(BattleService::class);
        $battle = $battleService->startBattle($user, $enemy, [$userCreature]);
        $battleService->playerAction($battle, $userCreature, $skill);

        $this->assertSame(2, $user->fresh()->level);
        $this->assertSame(30, $user->fresh()->xp);
    }
}
