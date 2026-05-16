<?php

namespace App\Enums;

enum QuestObjectiveType: string
{
    case CompleteTrainingSessions = 'complete_training_sessions';
    case CompleteLessons = 'complete_lessons';
    case AnswerCorrectQuestions = 'answer_correct_questions';
    case DefeatEnemies = 'defeat_enemies';
    case DefeatBoss = 'defeat_boss';
    case ReachCreatureLevel = 'reach_creature_level';
    case CompleteLevel = 'complete_level';
    case EarnCoins = 'earn_coins';
}
