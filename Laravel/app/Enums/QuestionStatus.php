<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
