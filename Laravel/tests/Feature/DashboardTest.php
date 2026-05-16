<?php

namespace Tests\Feature;

use App\Models\Avatar\Avatar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_without_avatar_are_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('onboarding.avatar.create'));
    }

    public function test_authenticated_users_with_avatar_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        Avatar::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Scout',
            'style' => 'strategist',
            'colors' => [
                'hair' => '#8C5A43',
                'skin' => '#F2C9A5',
                'accent' => '#4C6FFF',
            ],
            'equipped_items' => [
                'body' => config('avatar.defaults.body'),
                'hair' => config('avatar.defaults.hair'),
                'outfit_preset' => 'strategist',
                'equipment' => [
                    'back' => 'back_00_c00',
                    'shoes' => 'feet_00_c00',
                    'pants' => 'bottom_00_c00',
                    'top' => 'chest_00_c00',
                ],
                'starter_class' => 'stratege',
            ],
        ]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Choisissez votre prochaine mission');
        $response->assertSee('Hall des échos');
        $response->assertSee('avatar-assets/bodies/Human/Female/Idle.png', false);
        $response->assertSee('avatar-assets/hairs/bob/female/black.png', false);
    }
}
