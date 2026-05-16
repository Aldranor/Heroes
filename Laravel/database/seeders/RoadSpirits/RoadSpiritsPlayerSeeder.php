<?php

namespace Database\Seeders\RoadSpirits;

use App\Models\Avatar\Avatar;
use App\Models\Companions\Companion;
use App\Models\Creatures\Creature;
use App\Models\User;
use App\Models\World\Level;
use App\Models\World\UserLevelProgress;
use App\Services\RewardService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoadSpiritsPlayerSeeder extends Seeder
{
    public function run(): void
    {
        $rewardService = app(RewardService::class);

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'hero_class' => 'strategist',
                'combat_role' => 'hybrid',
                'specialization_slug' => 'tactician',
                'free_respecs' => max(1, (int) config('builds.respec.free_uses', 1)),
            ],
        );

        Avatar::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nickname' => 'Road Rookie',
                'style' => 'strategist',
                'colors' => [
                    'hair' => '#8C5A43',
                    'skin' => '#F2C9A5',
                    'accent' => '#4C6FFF',
                ],
                'equipped_items' => [
                    'body' => config('avatar.defaults.body'),
                    'hair' => config('avatar.defaults.hair'),
                    'outfit' => 'strategist',
                    'starter_class' => 'stratege',
                ],
            ],
        );

        foreach (['gardien-des-panneaux', 'stratege-des-priorites', 'eclaireur-de-vitesse'] as $creatureSlug) {
            $rewardService->grantCreature(
                $user,
                Creature::query()->where('slug', $creatureSlug)->firstOrFail(),
            );
        }

        foreach (['stratege', 'observateur', 'eclaireur'] as $companionSlug) {
            $rewardService->grantCompanion(
                $user,
                Companion::query()->where('slug', $companionSlug)->firstOrFail(),
                [
                    'is_favorite' => $companionSlug === 'stratege',
                ],
            );
        }

        $rewardService->grantCoins($user, max(0, 200 - $user->coins));

        $firstLevel = Level::query()->where('slug', 'ruelle-des-panneaux')->firstOrFail();

        UserLevelProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'level_id' => $firstLevel->id,
            ],
            [
                'status' => 'unlocked',
            ],
        );
    }
}
