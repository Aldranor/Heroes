<?php

namespace App\Enums;

enum QuestType: string
{
    case Training = 'training';
    case Battle = 'battle';
    case Boss = 'boss';
    case Collection = 'collection';
    case Arena = 'arena';
    case Daily = 'daily';
    case Story = 'story';
}
