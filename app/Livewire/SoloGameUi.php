<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Card;
use App\Dealer;
use App\Decks\EmojiDeck;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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

    public bool $hasStarted = false;

    public ?int $startedAt = null;

    public ?int $finishedAt = null;

    public bool $isAnimating = false;

    public ?string $pendingMatchSymbol = null;

    public int $rotationSeed = 0;

    public function mount(): void
    {
        $this->resetGame();
    }

    public function startNewGame(): void
    {
        $this->hasStarted = true;
        $this->startedAt = Carbon::now()->timestamp;
        $this->finishedAt = null;
        $this->isOver = false;
        $this->isAnimating = false;
        $this->pendingMatchSymbol = null;
        $this->rotationSeed = random_int(1, PHP_INT_MAX);

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
        if (! $this->hasStarted || $this->isAnimating) {
            return;
        }

        $this->selectedPileSymbol = $this->selectedPileSymbol === $symbol ? null : $symbol;

        $this->resolveSelection();
    }

    public function selectHandSymbol(string $symbol): void
    {
        if (! $this->hasStarted || $this->isAnimating) {
            return;
        }

        $this->selectedHandSymbol = $this->selectedHandSymbol === $symbol ? null : $symbol;

        $this->resolveSelection();
    }

    public function getDurationProperty(): ?string
    {
        if (! $this->hasStarted || $this->startedAt === null || $this->finishedAt === null) {
            return null;
        }

        $seconds = max(0, $this->finishedAt - $this->startedAt);

        return $this->formatDurationForHumans($seconds);
    }

    /**
     * @return string[]
     */
    public function getNextHandCardProperty(): array
    {
        $count = count($this->hand);

        if ($count < 2) {
            return [];
        }

        return $this->hand[$count - 2];
    }

    public function completeMatch(): void
    {
        if (! $this->hasStarted || ! $this->isAnimating || $this->pendingMatchSymbol === null) {
            return;
        }

        $symbol = $this->pendingMatchSymbol;

        try {
            $this->handleMatch($symbol);
        } finally {
            $this->isAnimating = false;
            $this->pendingMatchSymbol = null;
        }
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

        if ($this->isAnimating) {
            return;
        }

        $this->isAnimating = true;
        $this->pendingMatchSymbol = $this->selectedPileSymbol;
        $this->dispatch('spotit-match');
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

        if ($this->hasStarted && $this->isOver && $this->finishedAt === null) {
            $this->finishedAt = Carbon::now()->timestamp;
        }
    }

    private function resetSelections(): void
    {
        $this->selectedPileSymbol = null;
        $this->selectedHandSymbol = null;
    }

    private function resetGame(): void
    {
        $this->pileCard = [];
        $this->hand = [];
        $this->handCard = [];
        $this->pileCount = 0;
        $this->isOver = false;
        $this->hasStarted = false;
        $this->startedAt = null;
        $this->finishedAt = null;
        $this->isAnimating = false;
        $this->pendingMatchSymbol = null;
        $this->rotationSeed = 0;

        $this->resetSelections();
    }

    /**
     * @return string[]
     */
    private function serializeCard(Card $card): array
    {
        return array_keys($card->getSymbols());
    }

    private function formatDurationForHumans(int $seconds): string
    {
        $seconds = max(0, $seconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.Str::plural('hour', $hours);
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' '.Str::plural('minute', $minutes);
        }

        if ($remainingSeconds > 0 || $parts === []) {
            $parts[] = $remainingSeconds.' '.Str::plural('second', $remainingSeconds);
        }

        return implode(' ', $parts);
    }

    /**
     * @param  string[]  $card
     * @return array<string, int>
     */
    public function rotationsForCard(string $scope, array $card): array
    {
        $degrees = [-28, -20, -12, -4, 4, 12, 20, 28];
        $seed = $this->rotationSeed.'|'.$scope.'|'.implode('|', $card);

        $symbols = array_values($card);

        usort($symbols, function (string $a, string $b) use ($seed): int {
            return (int) crc32($seed.'|'.$a) <=> (int) crc32($seed.'|'.$b);
        });

        $rotations = [];

        foreach ($symbols as $index => $symbol) {
            $rotations[$symbol] = $degrees[$index % count($degrees)];
        }

        return $rotations;
    }

    public function render(): View
    {
        return view('livewire.solo-game-ui');
    }
}
