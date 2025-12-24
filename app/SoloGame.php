<?php

declare(strict_types=1);

namespace App;

final class SoloGame
{
    /**
     * @param  Card[]  $deck  An array of cards.
     */
    public function __construct(
        private array $deck,
        private Player $player,
    ) {}

    public function start(): self
    {

        return $this;
    }

    public function getDeck(): array
    {
        return $this->deck;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
