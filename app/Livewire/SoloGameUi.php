<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Card;
use App\Dealer;
use App\Decks\EmojiDeck;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SoloGameUi extends Component
{
    /**
     * @var string[]
     */
    public array $pileCard = [];

    /**
     * @var string[]
     */
    public array $handRotations = [];

    /**
     * @var string[]
     */
    public array $handCard = [];

    /**
     * @var string[]
     */
    public array $pileRotations = [];

    public int $pileCount = 0;

    public int $handRemaining = 0;

    public bool $isOver = false;

    public ?string $selectedPileSymbol = null;

    public ?string $selectedHandSymbol = null;

    public bool $hasStarted = false;

    public ?int $startedAt = null;

    public ?int $finishedAt = null;

    public bool $isAnimating = false;

    public ?string $pendingMatchSymbol = null;

    public int $rotationSeed = 0;

    #[Locked]
    public string $gameKey = '';

    public function mount(): void
    {
        $this->ensureGameKey();
        $this->resetGame();
    }

    public function startNewGame(): void
    {
        $this->ensureGameKey();

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
        $this->putHandStack(array_map(fn (Card $card): array => $this->serializeCard($card), $hand));
        $this->pileCount = 1;

        $this->syncHandCard();
        $this->syncRotations();
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

        $hand = $this->getHandStack();
        $matchedCard = array_pop($hand);
        $this->putHandStack($hand);

        if ($matchedCard === null) {
            $this->syncHandCard();
            $this->syncRotations();
            $this->resetSelections();

            return;
        }

        $this->pileCard = $matchedCard;
        $this->pileCount++;

        $this->syncHandCard();
        $this->syncRotations();
        $this->resetSelections();
    }

    private function syncHandCard(): void
    {
        $hand = $this->getHandStack();

        $this->handCard = $hand !== [] ? end($hand) : [];
        $this->handRemaining = count($hand);
        $this->isOver = $hand === [];

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
        $this->clearGameSession();
        $this->handCard = [];
        $this->handRemaining = 0;
        $this->handRotations = [];
        $this->pileRotations = [];
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

    private function ensureGameKey(): void
    {
        if ($this->gameKey !== '') {
            return;
        }

        $this->gameKey = (string) Str::uuid();
    }

    private function gameSessionKey(string $suffix): string
    {
        return "spotit.solo.{$this->gameKey}.{$suffix}";
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function getHandStack(): array
    {
        /** @var array<int, array<int, string>> $hand */
        $hand = session()->get($this->gameSessionKey('hand'), []);

        return $hand;
    }

    /**
     * @param  array<int, array<int, string>>  $hand
     */
    private function putHandStack(array $hand): void
    {
        session()->put($this->gameSessionKey('hand'), $hand);
    }

    private function clearGameSession(): void
    {
        if ($this->gameKey === '') {
            return;
        }

        session()->forget("spotit.solo.{$this->gameKey}");
    }

    private function syncRotations(): void
    {
        $this->pileRotations = $this->pileCard !== [] ? $this->rotationsForCard('pile', $this->pileCard) : [];
        $this->handRotations = $this->handCard !== [] ? $this->rotationsForCard('hand', $this->handCard) : [];
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
        $seed = $this->rotationSeed.'|'.$scope.'|'.implode('|', $card);

        $symbols = array_values($card);

        usort($symbols, function (string $a, string $b) use ($seed): int {
            return (int) crc32($seed.'|'.$a) <=> (int) crc32($seed.'|'.$b);
        });

        $rotations = [];
        $used = [];

        foreach ($symbols as $symbol) {
            $hash = crc32($seed.'|rotation|'.$symbol);

            if ($hash < 0) {
                $hash += 4294967296;
            }

            $rotation = $hash % 361;

            for ($attempts = 0; $attempts < 361; $attempts++) {
                if (! isset($used[$rotation])) {
                    break;
                }

                $rotation = ($rotation + 1) % 361;
            }

            $used[$rotation] = true;
            $rotations[$symbol] = $rotation;
        }

        return $rotations;
    }

    public function render(): View
    {
        return view('livewire.solo-game-ui');
    }
}
