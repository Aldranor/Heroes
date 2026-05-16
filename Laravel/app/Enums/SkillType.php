<?php

namespace App\Enums;

enum SkillType: string
{
    case Attack = 'attack';
    case Defense = 'defense';
    case Shield = 'shield';
    case Heal = 'heal';
    case Buff = 'buff';
    case Debuff = 'debuff';
    case AreaAttack = 'area_attack';
    case PenaltyReduction = 'penalty_reduction';
    case ThemeBonus = 'theme_bonus';
}
