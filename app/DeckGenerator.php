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
     * Each card generated will have {$this->perCard} symbols in it.
     *
     * @return Card[]
     */
    public function generate(): array
    {
        if (empty($this->symbols)) {
            throw new \LogicException('Symbols must be set before generating a deck.');
        }

        $n = $this->order;
        $deck = [];

        // Card 0: Contains the first n+1 symbols (symbols 0 through n)
        $card0 = new Card($this->perCard);
        $card0->setSymbols(array_slice($this->symbols, 0, $n + 1));
        $deck[] = $card0;

        // Cards 1 through n: Each contains symbol 0 plus a "column" of symbols
        for ($i = 0; $i < $n; $i++) {
            $card = new Card($this->perCard);
            $cardSymbols = [$this->symbols[0]];

            for ($j = 0; $j < $n; $j++) {
                $symbolIdx = ($n + 1) + $j + ($i * $n);
                $cardSymbols[] = $this->symbols[$symbolIdx];
            }

            $card->setSymbols($cardSymbols);
            $deck[] = $card;
        }

        // Remaining n² cards: Constructed using projective plane geometry
        for ($s = 0; $s < $n; $s++) {
            for ($t = 0; $t < $n; $t++) {
                $card = new Card($this->perCard);
                $cardSymbols = [$this->symbols[$s + 1]];

                for ($k = 0; $k < $n; $k++) {
                    $symbolIdx = ($n + 1) + (($t + $s * $k) % $n) + ($k * $n);
                    $cardSymbols[] = $this->symbols[$symbolIdx];
                }

                $card->setSymbols($cardSymbols);
                $deck[] = $card;
            }
        }

        return $deck;
    }
}
