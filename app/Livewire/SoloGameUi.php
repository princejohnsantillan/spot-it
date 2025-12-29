<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Card;
use App\Dealer;
use App\Decks\EmojiDeck;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class SoloGameUi extends Component
{
    /**
     * @var string[]
     */
    public array $pileCard = [];

    /**
     * @var array<int, array<int, string>>
     */
    public array $hand = [];

    /**
     * @var string[]
     */
    public array $handCard = [];

    public int $pileCount = 0;

    public bool $isOver = false;

    public ?string $selectedPileSymbol = null;

    public ?string $selectedHandSymbol = null;

    public function mount(): void
    {
        $this->startNewGame();
    }

    public function startNewGame(): void
    {
        $deck = (new EmojiDeck)->generate();

        $dealer = Dealer::using($deck)->shuffle();

        $pile = $dealer->top();

        $hand = [];
        $dealer->deal($hand);

        $this->pileCard = $this->serializeCard($pile);
        $this->hand = array_map(fn (Card $card): array => $this->serializeCard($card), $hand);
        $this->pileCount = 1;

        $this->syncHandCard();
        $this->resetSelections();
    }

    public function selectPileSymbol(string $symbol): void
    {
        $this->selectedPileSymbol = $this->selectedPileSymbol === $symbol ? null : $symbol;

        $this->resolveSelection();
    }

    public function selectHandSymbol(string $symbol): void
    {
        $this->selectedHandSymbol = $this->selectedHandSymbol === $symbol ? null : $symbol;

        $this->resolveSelection();
    }

    private function resolveSelection(): void
    {
        if ($this->selectedPileSymbol === null || $this->selectedHandSymbol === null) {
            return;
        }

        if ($this->selectedPileSymbol !== $this->selectedHandSymbol) {
            $this->resetSelections();
            $this->dispatch('spotit-shake');

            return;
        }

        $this->handleMatch($this->selectedPileSymbol);
    }

    private function handleMatch(string $symbol): void
    {
        if ($this->isOver) {
            return;
        }

        if (! in_array($symbol, $this->pileCard, true) || ! in_array($symbol, $this->handCard, true)) {
            $this->resetSelections();
            $this->dispatch('spotit-shake');

            return;
        }

        $matchedCard = array_pop($this->hand);

        if ($matchedCard === null) {
            $this->syncHandCard();
            $this->resetSelections();

            return;
        }

        $this->pileCard = $matchedCard;
        $this->pileCount++;

        $this->syncHandCard();
        $this->resetSelections();
    }

    private function syncHandCard(): void
    {
        $this->handCard = $this->hand !== [] ? end($this->hand) : [];
        $this->isOver = $this->hand === [];
    }

    private function resetSelections(): void
    {
        $this->selectedPileSymbol = null;
        $this->selectedHandSymbol = null;
    }

    /**
     * @return string[]
     */
    private function serializeCard(Card $card): array
    {
        return array_keys($card->getSymbols());
    }

    public function render(): View
    {
        return view('livewire.solo-game-ui');
    }
}
