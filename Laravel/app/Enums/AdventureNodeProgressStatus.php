<?php

namespace App\Enums;

enum AdventureNodeProgressStatus: string
{
    case Locked = 'locked';
    case Available = 'available';
    case Completed = 'completed';
}
