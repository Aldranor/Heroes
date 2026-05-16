<?php

namespace App\Enums;

enum QuestionType: string
{
    case VisualRecognition = 'visual_recognition';
    case Association = 'association';
    case Definition = 'definition';
    case Situation = 'situation';
    case ReverseLookup = 'reverse_lookup';
}
