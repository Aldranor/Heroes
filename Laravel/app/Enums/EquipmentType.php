<?php

namespace App\Enums;

enum EquipmentType: string
{
    case Weapon = 'weapon';
    case Armor = 'armor';
    case Charm = 'charm';
    case Accessory = 'accessory';
}
