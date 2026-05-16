<?php

namespace App\Enums;

enum SkillTarget: string
{
    case Enemy = 'enemy';
    case AllEnemies = 'all_enemies';
    case Self = 'self';
    case Ally = 'ally';
}
