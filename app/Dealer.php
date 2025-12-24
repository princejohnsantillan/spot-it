<?php

declare(strict_types=1);

namespace App;

final class Dealer
{

    /**
     * @param Card[] $deck
     */
    public function __construct(private array $deck)
    {

    }

    public function shuffle(): Dealer
    {
        shuffle($this->deck);
        shuffle($this->deck);

        return $this;
    }


}
