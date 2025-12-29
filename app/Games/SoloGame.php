<?php

declare(strict_types=1);

namespace App\Games;

use App\Card;
use App\Contracts\Symbol;
use App\Dealer;
use App\Player;

final class SoloGame
{
    /**
     * @var Card[]
     */
    private array $discardPile = [];

    private int|false $startTime = false;

    private int|false $endTime = false;

    /**
     * @param  Card[]  $deck  An array of cards.
     */
    public function __construct(
        private array $deck,
        private Player $player,
    ) {}

    public function start(): self
    {
        $dealer = Dealer::using($this->deck)->shuffle();

        $this->discardPile[] = $dealer->top();

        $dealer->deal($this->player->hand);

        $this->startTime = time();

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function peak(): Card
    {
        return end($this->discardPile);
    }

    public function spotted(Symbol $symbol): void
    {
        if ($this->player->spotIt($this->peak(), $symbol)) {
            $this->discardPile[] = $this->player->top();
        }

        $this->gameCheck();
    }

    private function gameCheck(): void
    {
        if ($this->player->countHand() === 0) {
            $this->endTime = time();
        }
    }

    public function isOver(): bool
    {
        return $this->endTime !== false;
    }

    public function getDuration(): int
    {
        return $this->endTime - $this->startTime;
    }

    public function getStatus(): array
    {
        return [
            'pile' => count($this->discardPile),
            'hand' => $this->player->countHand(),
        ];
    }
}
