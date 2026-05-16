<?php

namespace App\Enums;

enum CreatureAcquisitionMethod: string
{
    case Starter = 'starter';
    case Adventure = 'adventure';
    case Arena = 'arena';
    case Shop = 'shop';
    case Event = 'event';
    case Achievement = 'achievement';
}
