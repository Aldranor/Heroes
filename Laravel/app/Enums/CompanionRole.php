<?php

namespace App\Enums;

enum CompanionRole: string
{
    case Dps = 'dps';
    case Precision = 'precision';
    case Fast = 'fast';
    case Tank = 'tank';
    case Support = 'support';
    case Balanced = 'balanced';
}
