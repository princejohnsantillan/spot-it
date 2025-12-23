<?php

declare(strict_types=1);

namespace App;

final class DeckGenerator
{

    private int $count;

    private int $perCard;

    private array $symbols;

    public function __construct(private int $prime = 7)
    {
        $this->count = ($prime * $prime) + $prime + 1;
        $this->perCard = $prime + 1;
    }

    public function getPrime(): int
    {
        return $this->prime;
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
        if(count($symbols) !== $this->count) {
            throw new \LogicException("The deck requires {$this->count} symbols.}");
        }

        $this->symbols = $symbols;

        return $this;
    }

    public function getSymbols(): array
    {
        return $this->symbols;
    }

    public function generate(): array
    {
        return [];
    }
}
