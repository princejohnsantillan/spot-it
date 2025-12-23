<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class DeckGenerator
{
    private int $count;

    private int $perCard;

    /**
     * @var Symbol[]
     */
    private array $symbols;

    public function __construct(private int $order = 7)
    {
        $this->count = ($order * $order) + $order + 1;
        $this->perCard = $order + 1;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getPerCard(): int
    {
        return $this->perCard;
    }

    public function setSymbols(array $symbols): self
    {
        if (count($symbols) !== $this->count) {
            throw new \LogicException("The deck requires {$this->count} symbols.}");
        }

        $this->symbols = $symbols;

        return $this;
    }

    public function getSymbols(): array
    {
        return $this->symbols;
    }

    /**
     * The generated deck will have {$this->count} number of cards.
     * Each card generated will have {$this->count} symbols in it.
     *
     * @return Card[]
     */
    public function generate(): array
    {
        if (empty($this->symbols)) {
            throw new \LogicException('Symbols must be set before generating a deck.');
        }

        $deck = [];

        return $deck;
    }
}
