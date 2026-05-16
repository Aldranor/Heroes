<?php

namespace App\Enums;

enum MasteryStatus: string
{
    case Unknown = 'unknown';
    case Learning = 'learning';
    case Mastered = 'mastered';
    case Automated = 'automated';
}
