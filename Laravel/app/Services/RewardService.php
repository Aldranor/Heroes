<?php

namespace App\Services;

use App\Models\Avatar\AvatarItem;
use App\Models\Avatar\UserAvatarItem;
use App\Models\Companions\Companion;
use App\Models\Companions\UserCompanion;
use App\Models\Commerce\Equipment;
use App\Models\Commerce\UserEquipment;
use App\Models\Creatures\Creature;
use App\Models\Creatures\UserCreature;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RewardService
{
    public function __construct(
        protected XpService $xpService,
    ) {}

    public function grantCoins(User $user, int $amount): User
    {
        if ($amount <= 0) {
            return $user->refresh();
        }

        return DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->coins += $amount;
            $lockedUser->save();

            return $lockedUser->refresh();
        });
    }

    public function grantUserXp(User $user, int $amount): User
    {
        return $this->xpService->grantUserXp($user, $amount);
    }

    public function grantSkillPoints(User $user, int $amount): User
    {
        if ($amount <= 0) {
            return $user->refresh();
        }

        return DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->skill_points += $amount;
            $lockedUser->save();

            return $lockedUser->refresh();
        });
    }

    public function grantSpecialSkillPoints(User $user, int $amount): User
    {
        if ($amount <= 0) {
            return $user->refresh();
        }

        return DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->special_skill_points += $amount;
            $lockedUser->save();

            return $lockedUser->refresh();
        });
    }

    public function grantCreature(User $user, Creature $creature, array $attributes = []): UserCreature
    {
        return DB::transaction(function () use ($user, $creature, $attributes) {
            $userCreature = UserCreature::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'creature_id' => $creature->id,
                ],
                array_merge([
                    'level' => 1,
                    'xp' => 0,
                    'current_hp' => $creature->base_hp,
                    'acquired_at' => now(),
                ], $attributes),
            );

            $this->xpService->unlockSkills($userCreature->refresh());

            return $userCreature->refresh();
        });
    }

    public function grantCompanion(User $user, Companion $companion, array $attributes = []): UserCompanion
    {
        return DB::transaction(function () use ($user, $companion, $attributes) {
            $userCompanion = UserCompanion::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'companion_id' => $companion->id,
                ],
                array_merge([
                    'level' => 1,
                    'xp' => 0,
                    'current_hp' => $companion->base_hp,
                ], $attributes),
            );

            $this->xpService->unlockCompanionSkills($userCompanion->refresh()->loadMissing('companion.skills'));

            return $userCompanion->refresh();
        });
    }

    public function grantEquipment(User $user, Equipment $equipment): UserEquipment
    {
        return UserEquipment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'equipment_id' => $equipment->id,
            ],
            [
                'acquired_at' => now(),
            ],
        );
    }

    public function grantAvatarItem(User $user, AvatarItem $avatarItem): UserAvatarItem
    {
        return UserAvatarItem::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'avatar_item_id' => $avatarItem->id,
            ],
            [
                'acquired_at' => now(),
            ],
        );
    }
}
