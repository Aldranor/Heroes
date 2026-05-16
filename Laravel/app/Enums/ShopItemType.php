<?php

namespace App\Enums;

enum ShopItemType: string
{
    case Equipment = 'equipment';
    case AvatarItem = 'avatar_item';
    case Creature = 'creature';
}
