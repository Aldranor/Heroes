<?php

namespace App\Enums;

enum ArenaRunStatus: string
{
    case Ongoing = 'ongoing';
    case Won = 'won';
    case Lost = 'lost';
}
