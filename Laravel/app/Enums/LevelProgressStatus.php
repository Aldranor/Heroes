<?php

namespace App\Enums;

enum LevelProgressStatus: string
{
    case Locked = 'locked';
    case Unlocked = 'unlocked';
    case Completed = 'completed';
}
