<?php

namespace App\Enums;

enum CompanionAcquisitionMethod: string
{
    case Starter = 'starter';
    case Quest = 'quest';
    case Shop = 'shop';
    case Reward = 'reward';
    case Achievement = 'achievement';
}
