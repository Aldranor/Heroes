<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\ShopItemType;
use App\Models\Commerce\ShopItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShopService
{
    public function __construct(
        protected RewardService $rewardService,
    ) {}

    public function purchase(User $user, ShopItem $shopItem): Model
    {
        $shopItem->loadMissing('item');

        if (! $shopItem->is_active) {
            throw new InvalidArgumentException('This shop item is not currently available.');
        }

        if ($shopItem->currency !== Currency::Coins) {
            throw new InvalidArgumentException('Only coin-based purchases are supported in the MVP.');
        }

        if (! $shopItem->item) {
            throw new InvalidArgumentException('The requested shop item is invalid.');
        }

        return DB::transaction(function () use ($user, $shopItem) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->coins < $shopItem->price) {
                throw new InvalidArgumentException('The player does not have enough coins.');
            }

            $lockedUser->coins -= $shopItem->price;
            $lockedUser->save();

            return match ($shopItem->item_type) {
                ShopItemType::Equipment => $this->rewardService->grantEquipment($lockedUser, $shopItem->item),
                ShopItemType::AvatarItem => $this->rewardService->grantAvatarItem($lockedUser, $shopItem->item),
                ShopItemType::Creature => $this->rewardService->grantCreature($lockedUser, $shopItem->item),
            };
        });
    }
}
