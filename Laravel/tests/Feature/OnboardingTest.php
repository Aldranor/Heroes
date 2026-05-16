<?php

namespace Tests\Feature;

use App\Models\Companions\Companion;
use App\Models\User;
use Database\Seeders\RoadSpirits\RoadSpiritsCompanionSeeder;
use Database\Seeders\RoadSpirits\RoadSpiritsLearningSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_avatar_onboarding(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('onboarding.avatar.create'));

        $response->assertOk();
        $response->assertSee('Crée ton héros');
        $response->assertSee('Choisis ta classe');
        $response->assertSee('Humaine');
        $response->assertSee('Saurienne');
        $response->assertSee('Carré');
        $response->assertSee('Noir');
        $response->assertSee('Variantes');
        $response->assertSee('data-avatar-template="body"', false);
        $response->assertSee('human-female', false);
        $response->assertSee('avatar-assets/bodies/Human/Female/Idle.png', false);
        $response->assertDontSee('onboarding.avatar.body.average', false);
        $response->assertDontSee('onboarding.avatar.hair.short', false);
    }

    public function test_onboarding_creates_avatar_assigns_starters_and_redirects_to_hub(): void
    {
        $this->seed([
            RoadSpiritsLearningSeeder::class,
            RoadSpiritsCompanionSeeder::class,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.avatar.store'), [
            'nickname' => 'Road Rookie',
            'body_asset' => 'human-female',
            'hair_asset' => 'bob--female--black',
            'starter_choice' => 'eclaireur',
            'colors' => [
                'hair' => '#8C5A43',
            ],
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('avatars', [
            'user_id' => $user->id,
            'nickname' => 'Road Rookie',
            'style' => 'scout',
        ]);

        $avatar = $user->fresh()->avatar;
        $this->assertSame('human-female', data_get($avatar->equipped_items, 'body'));
        $this->assertSame('bob--female--black', data_get($avatar->equipped_items, 'hair'));
        $this->assertSame('scout', data_get($avatar->equipped_items, 'outfit_preset'));
        $this->assertSame('eclaireur', data_get($avatar->equipped_items, 'starter_class'));
        $this->assertSame('cape-solid-female-orange', data_get($avatar->equipped_items, 'equipment.back'));
        $this->assertSame('feet-boots-female-orange', data_get($avatar->equipped_items, 'equipment.shoes'));
        $this->assertSame('legs-pants-female-orange', data_get($avatar->equipped_items, 'equipment.pants'));
        $this->assertSame('torso-clothes-tunic-female-orange', data_get($avatar->equipped_items, 'equipment.top'));
        $this->assertSame('#F7D7C4', data_get($avatar->colors, 'skin'));

        $starters = Companion::query()
            ->whereIn('slug', ['stratege', 'observateur', 'eclaireur'])
            ->pluck('id');

        $this->assertSame(3, $user->fresh()->userCompanions()->count());

        foreach ($starters as $companionId) {
            $this->assertDatabaseHas('user_companions', [
                'user_id' => $user->id,
                'companion_id' => $companionId,
                'level' => 1,
            ]);
        }

        $this->assertDatabaseHas('user_companions', [
            'user_id' => $user->id,
            'companion_id' => Companion::query()->where('slug', 'eclaireur')->value('id'),
            'is_favorite' => true,
        ]);
    }

    public function test_onboarding_reports_missing_starter_companions(): void
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(PreventRequestForgery::class);

        Companion::query()
            ->whereIn('slug', ['stratege', 'observateur', 'eclaireur'])
            ->delete();

        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing starter companions: stratege, observateur, eclaireur');

        $this->actingAs($user)->post(route('onboarding.avatar.store'), [
            'nickname' => 'Road Rookie',
            'body_asset' => 'human-female',
            'hair_asset' => 'bob--female--black',
            'starter_choice' => 'stratege',
        ]);
    }

    public function test_hair_variants_endpoint_returns_model_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('onboarding.avatar.hair-variants', [
            'model' => 'bob',
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'profiles' => [
                    'female' => [
                        [
                            'key',
                            'label',
                            'hair_color',
                            'src',
                        ],
                    ],
                ],
            ]);

        $this->assertStringContainsString('/avatar-assets/hairs/', $response->json('profiles.female.0.src'));
    }
}
