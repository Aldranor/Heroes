<?php

namespace App\Enums;

enum TrainingMode: string
{
    case Mixed = 'mixed';
    case VisualRecognition = 'visual_recognition';
    case Association = 'association';
    case Definition = 'definition';
    case Situation = 'situation';
    case ReverseLookup = 'reverse_lookup';
    case Traps = 'traps';
}
