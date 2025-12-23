<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class Card
{
    /** @var Symbol[] $symbols  */
    private array $symbols;

    public function __construct(private int $count = 8){

    }

    public function setSymbols(array $symbols): self
    {
        $symbols = array_unique($symbols);

        if(count($symbols) !== $this->count){
            throw new \LogicException("This card needs {$this->count} unique symbols.");
        }

        $this->symbols =  collect($symbols)->mapWithKeys(fn(Symbol $symbol) => [$symbol->getId() => $symbol])->toArray();

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


    public function spotIt(Card $card): Symbol|false
    {
        $common =  array_intersect_key($this->getSymbols(), $card->getSymbols());

        $count = count($common);

        if($count === 0){
            return false;
        }

        if($count > 1){
            throw new \LogicException("Invalid cards: only one symbol should be spotted.");
        }

        return array_pop($common);
    }
}
