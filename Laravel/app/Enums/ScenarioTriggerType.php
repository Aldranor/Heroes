<?php

namespace App\Enums;

enum ScenarioTriggerType: string
{
    case ZoneIntro = 'zone_intro';
    case LevelIntro = 'level_intro';
    case BeforeBattle = 'before_battle';
    case AfterBattle = 'after_battle';
    case QuestIntro = 'quest_intro';
    case QuestComplete = 'quest_complete';
    case BossIntro = 'boss_intro';
}
