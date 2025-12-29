<?php

declare(strict_types=1);

namespace App;

final class Dealer
{
    /**
     * @param  Card[]  $deck
     */
    public function __construct(private array &$deck) {}

    /**
     * @param  Card[]  $deck
     */
    public static function using(array &$deck): Dealer
    {
        return new static($deck);
    }

    public function shuffle(): Dealer
    {
        shuffle($this->deck);
        shuffle($this->deck);

        return $this;
    }

    public function deal(&...$piles): array
    {
        $pilesCount = count($piles);

        if($pilesCount === 0){
            return $this->deck;
        }

        while($this->deck !== []){
            $cardsCount = count($this->deck);

            if($cardsCount/$pilesCount < 1){
                break;
            }

            foreach($piles as &$pile){

                $top = array_pop($this->deck);

                if($top === null){
                    break;
                }

                $pile[] = $top;
            }
        }

        return $this->deck;
    }

    public function peak(): Card
    {
        return end($this->deck);
    }

    public function top(): Card
    {
        return array_pop($this->deck);
    }

    public function shuffleAndTop(): Card
    {
        $this->shuffle();
        return $this->top();
    }

    public function shuffleAndDeal(& ...$piles): array
    {
        $this->shuffle();
        return $this->deal(...$piles);
    }

}
