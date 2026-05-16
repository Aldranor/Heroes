<?php

namespace Tests\Unit\Services;

use App\Models\Creatures\Skill;
use App\Services\RewardService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_user_xp_gain_increments_progress(): void
    {
        $user = $this->createUser();

        app(XpService::class)->grantUserXp($user, 40);

        $this->assertSame(40, $user->fresh()->xp);
        $this->assertSame(1, $user->fresh()->level);
    }

    public function test_creature_xp_gain_increments_progress(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $creature = $this->createCreature($domain, $category);
        $user = $this->createUser();
        $userCreature = app(RewardService::class)->grantCreature($user, $creature);

        app(XpService::class)->grantCreatureXp($userCreature, 20);

        $this->assertSame(20, $userCreature->fresh()->xp);
        $this->assertSame(1, $userCreature->fresh()->level);
    }

    public function test_level_up_consumes_threshold_and_increases_level(): void
    {
        $user = $this->createUser(['xp' => 90]);

        app(XpService::class)->grantUserXp($user, 20);

        $this->assertSame(2, $user->fresh()->level);
        $this->assertSame(10, $user->fresh()->xp);
    }

    public function test_unlock_skill_when_creature_reaches_required_level(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain);
        $defaultSkill = $this->createSkill(['unlock_level' => 1]);
        $levelThreeSkill = $this->createSkill([
            'name' => 'Level 3 Skill',
            'slug' => 'level-3-skill',
            'unlock_level' => 3,
        ]);
        $creature = $this->createCreature($domain, $category, ['default_skill' => $defaultSkill]);
        $creature->skills()->syncWithoutDetaching([
            $levelThreeSkill->id => [
                'unlock_level' => 3,
                'sort_order' => 2,
                'is_default' => false,
            ],
        ]);

        $user = $this->createUser();
        $userCreature = app(RewardService::class)->grantCreature($user, $creature);

        app(XpService::class)->grantCreatureXp($userCreature, 250);

        $this->assertSame(3, $userCreature->fresh()->level);
        $this->assertTrue(
            $userCreature->fresh()->userCreatureSkills()->where('skill_id', $levelThreeSkill->id)->exists(),
        );
        $this->assertSame(2, $userCreature->fresh()->userCreatureSkills()->count());
    }
}
