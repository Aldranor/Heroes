<?php

namespace App\Enums;

enum ScenarioActionType: string
{
    case None = 'none';
    case StartBattle = 'start_battle';
    case StartTraining = 'start_training';
    case GiveReward = 'give_reward';
    case UnlockLevel = 'unlock_level';
    case CompleteQuest = 'complete_quest';
}
