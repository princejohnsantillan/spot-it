<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class Player
{
    /**
     * The player's hand, the cards dealt to him.
     *
     * @var Card[]
     */
    public array $hand = [];

    /**
     * @param  string  $id  The player's unique identifier.
     * @param  string  $name  The player's name.
     * @param  Card[]  $hand
     */
    public function __construct(
        private string $id,
        private string $name,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function countHand(): int
    {
        return count($this->hand);
    }

    public function addCard(Card $card): array
    {
        $this->hand[] = $card;

        return $this->hand;
    }

    public function peak(): Card
    {
        return end($this->hand);
    }

    public function top(): Card
    {
        return array_pop($this->hand);
    }

    public function spotIt(Card $card, Symbol $symbol): bool
    {
        return $this->peak()->spotItSymbol($card)->isSymbol($symbol);
    }
}
