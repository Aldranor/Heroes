<?php

namespace App\Enums;

enum BattleStatus: string
{
    case Ongoing = 'ongoing';
    case Won = 'won';
    case Lost = 'lost';
}
