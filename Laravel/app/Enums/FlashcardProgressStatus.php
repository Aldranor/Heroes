<?php

namespace App\Enums;

enum FlashcardProgressStatus: string
{
    case Viewed = 'viewed';
    case Mastered = 'mastered';
}
