<?php

namespace Tests\Unit\Services;

use App\Enums\CompanionRole;
use App\Services\RewardService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanionXpServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_user_can_own_companions(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain, ['slug' => 'priority']);
        $companion = $this->createCompanion($category, [
            'name' => 'Stratege',
            'slug' => 'stratege-test',
            'role' => CompanionRole::Dps,
        ]);
        $user = $this->createUser();

        $userCompanion = app(RewardService::class)->grantCompanion($user, $companion);

        $this->assertSame($user->id, $userCompanion->user_id);
        $this->assertSame($companion->id, $userCompanion->companion_id);
        $this->assertCount(1, $user->fresh()->userCompanions);
    }

    public function test_companion_xp_uses_specialization_values(): void
    {
        $domain = $this->createDomain();
        $priority = $this->createCategory($domain, ['slug' => 'priority']);
        $signs = $this->createCategory($domain, ['slug' => 'signs']);
        $companion = $this->createCompanion($priority, ['role' => CompanionRole::Dps]);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion);
        $xpService = app(XpService::class);

        $this->assertSame(15, $xpService->calculateCompanionAnswerXp($userCompanion, $priority, true));
        $this->assertSame(8, $xpService->calculateCompanionAnswerXp($userCompanion, $signs, true));
        $this->assertSame(5, $xpService->calculateCompanionAnswerXp($userCompanion, $priority, false));
    }

    public function test_companion_levels_up_when_threshold_is_reached(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain, ['slug' => 'speed']);
        $companion = $this->createCompanion($category, ['base_hp' => 72]);
        $user = $this->createUser();
        $userCompanion = $this->createUserCompanion($user, $companion, ['xp' => 95, 'current_hp' => 40]);

        $userCompanion = app(XpService::class)->grantCompanionXp($userCompanion, 10);

        $this->assertSame(2, $userCompanion->level);
        $this->assertSame(5, $userCompanion->xp);
        $this->assertSame($userCompanion->maxHp(), $userCompanion->current_hp);
    }

    public function test_companion_unlocks_skills_at_expected_levels(): void
    {
        $domain = $this->createDomain();
        $category = $this->createCategory($domain, ['slug' => 'mixed']);
        $companion = $this->createCompanion($category);
        $skills = [
            $this->createSkill(['slug' => 'companion-basic', 'unlock_level' => 1]),
            $this->createSkill(['slug' => 'companion-defense', 'unlock_level' => 3]),
            $this->createSkill(['slug' => 'companion-focus', 'unlock_level' => 5]),
            $this->createSkill(['slug' => 'companion-heal', 'unlock_level' => 8]),
        ];

        $companion->skills()->sync([
            $skills[0]->id => ['unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
            $skills[1]->id => ['unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
            $skills[2]->id => ['unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
            $skills[3]->id => ['unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
        ]);

        $user = $this->createUser();
        $userCompanion = app(RewardService::class)->grantCompanion($user, $companion);

        $this->assertSame(1, $userCompanion->fresh()->userCompanionSkills()->count());

        $userCompanion = app(XpService::class)->grantCompanionXp($userCompanion, 250);

        $this->assertSame(3, $userCompanion->level);
        $this->assertCount(2, $userCompanion->fresh()->userCompanionSkills);

        $userCompanion = app(XpService::class)->grantCompanionXp($userCompanion, 600);

        $this->assertSame(5, $userCompanion->level);
        $this->assertCount(3, $userCompanion->fresh()->userCompanionSkills);
    }
}
