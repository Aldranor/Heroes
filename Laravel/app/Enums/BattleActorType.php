<?php

namespace App\Enums;

enum BattleActorType: string
{
    case Hero = 'hero';
    case UserCompanion = 'user_companion';
    case UserCreature = 'user_creature';
    case Enemy = 'enemy';
}
