<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class Player
{
    /**
     * @param string $id The player's unique identifier.
     * @param string $name The player's name.
     * @param Card[] $hand The player's hand, the cards dealt to him.
     */
    public function __construct(
        private string $id,
        private string $name,
        private array $hand = []
    ){
    }

    public function setHand(array $hand): self
    {
        $this->hand = $hand;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHand(): array
    {
        return $this->hand;
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

    public function releaseTopCard(): Card
    {
        return array_pop($this->hand);
    }

    /**
     * Release the top card if the symbol is on it.
     */
    public function spotAndRelease(Symbol $symbol): Card|false
    {
        $hand = $this->hand;

        $top = array_pop($hand);

        if($top->contains($symbol)){
            $this->releaseTopCard();
        }

        return false;
    }
}
