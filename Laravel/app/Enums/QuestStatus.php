<?php

namespace App\Enums;

enum QuestStatus: string
{
    case Available = 'available';
    case Accepted = 'accepted';
    case Completed = 'completed';
    case Claimed = 'claimed';
}
