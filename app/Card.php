<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class Card
{
    /** @var Symbol[] $symbols  */
    private array $symbols;

    /** @var string[] $symbolIds  */
    private array $symbolIds;

    public function __construct(private int $count = 8){

    }

    public function setSymbols(array $symbols): self
    {
        $symbols = array_unique($symbols);

        if(count($symbols) !== $this->count){
            throw new \LogicException("This card needs {$this->count} unique symbols.");
        }

        $this->symbols = $symbols;

        $this->symbolIds = collect($this->symbols)->map(fn(Symbol $symbol) => $symbol->getId())->toArray();

        return $this;
    }

    public function getSymbols(): array
    {
        return $this->symbols;
    }

    public function getSymbolIds(): array
    {
        return $this->symbolIds;
    }

    public function contains(Symbol $symbol): bool
    {
        return in_array($symbol->getId(), $this->getSymbolIds());
    }
}
