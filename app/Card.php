<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class Card
{
    /**
     * An array of unique symbols.
     *
     * @var Symbol[]
     */
    private array $symbols;

    public function __construct(private int $count = 8) {}

    public function setSymbols(array $symbols): self
    {
        $symbols = array_unique($symbols);

        if (count($symbols) !== $this->count) {
            throw new \LogicException("This card needs {$this->count} unique symbols.");
        }

        $this->symbols = collect($symbols)->mapWithKeys(fn (Symbol $symbol) => [$symbol->getId() => $symbol])->toArray();

        return $this;
    }

    public function getSymbols(): array
    {
        return $this->symbols;
    }

    public function contains(Symbol $symbol): bool
    {
        return in_array($symbol->getId(), array_keys($this->getSymbols()));
    }

    /*
     * Spot the one and only matching symbol on another card.
     */
    public function spotIt(Card $card): Symbol
    {
        $common = array_intersect_key($this->getSymbols(), $card->getSymbols());

        if (count($common) !== 1) {
            throw new \LogicException('Invalid cards: strictly one symbol should be spotted.');
        }

        return array_pop($common);
    }
}
