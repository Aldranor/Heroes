<?php

namespace App\Enums;

enum AdventureDialogueTrigger: string
{
    case MapStarted = 'map_started';
    case MapCompleted = 'map_completed';
    case NodeCompleted = 'node_completed';
}
