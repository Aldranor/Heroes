<?php

namespace App\Enums;

enum TrainingSessionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
