<?php

declare(strict_types=1);

namespace App\Multiplayer;

enum GameStatus: string
{
    case Waiting = 'waiting';
    case Playing = 'playing';
    case Finished = 'finished';
}
