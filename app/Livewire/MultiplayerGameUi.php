<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Events\GameEnded;
use App\Events\GameStarted;
use App\Events\PlayerLeftRoom;
use App\Events\PlayerMatchedCard;
use App\Multiplayer\GameRoom;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class MultiplayerGameUi extends Component
{
    #[Locked]
    public string $roomCode = '';

    #[Locked]
    public string $playerId = '';

    public string $playerName = '';

    public bool $isHost = false;

    public string $hostId = '';

    public string $status = 'waiting';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $players = [];

    /**
     * @var array<int, string>
     */
    public array $pileCard = [];

    /**
     * @var array<int, string>
     */
    public array $handCard = [];

    public int $cardsRemaining = 0;

    /**
     * @var array<string, int>
     */
    public array $pileRotations = [];

    /**
     * @var array<string, int>
     */
    public array $handRotations = [];

    public int $rotationSeed = 0;

    public ?string $selectedPileSymbol = null;

    public ?string $selectedHandSymbol = null;

    // Animation state - tracks if THIS player is animating their own match
    public bool $isAnimating = false;

    public ?string $pendingMatchSymbol = null;

    // Overlay state - shown for ALL players when a match happens
    public bool $showMatchOverlay = false;

    public ?string $overlayPlayerName = null;

    public ?string $overlaySymbol = null;

    public ?string $overlayPlayerId = null;

    public bool $overlayIsMe = false;

    /**
     * Pending card state - stored until overlay clears
     *
     * @var array<int, string>
     */
    public array $pendingPileCard = [];

    /**
     * @var array<int, string>
     */
    public array $pendingHandCard = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $pendingPlayers = [];

    public int $pendingCardsRemaining = 0;

    public ?string $winnerId = null;

    public string $winnerName = '';

    /**
     * @var array<string, int>
     */
    public array $scoreboard = [];

    public function mount(string $code): void
    {
        $this->roomCode = strtoupper($code);
        $this->playerId = session('guest_player_id', '');
        $this->playerName = session('guest_player_name', '');

        if (! $this->playerId || ! $this->playerName) {
            $this->redirect(route('multiplayer.lobby'), navigate: true);

            return;
        }

        $room = GameRoom::find($this->roomCode);

        if ($room === null) {
            $this->redirect(route('multiplayer.lobby'), navigate: true);

            return;
        }

        $this->syncFromRoom($room);
    }

    public function startGame(): void
    {
        $room = GameRoom::find($this->roomCode);

        if ($room === null || ! $room->isHost($this->playerId)) {
            return;
        }

        if (! $room->start()) {
            return;
        }

        $room->save();

        // Broadcast game started
        broadcast(new GameStarted(
            roomCode: $room->code,
            pileCard: $room->pileCard,
            players: array_map(fn ($p) => $p->toArray(), $room->players),
            rotationSeed: $room->rotationSeed,
        ));

        $this->syncFromRoom($room);
    }

    public function selectPileSymbol(string $symbol): void
    {
        // Don't allow selection if animating own match OR if overlay is showing
        if ($this->status !== 'playing' || $this->isAnimating) {
            return;
        }

        $this->selectedPileSymbol = $this->selectedPileSymbol === $symbol ? null : $symbol;
        $this->resolveSelection();
    }

    public function selectHandSymbol(string $symbol): void
    {
        // Don't allow selection if animating own match OR if overlay is showing
        if ($this->status !== 'playing' || $this->isAnimating) {
            return;
        }

        $this->selectedHandSymbol = $this->selectedHandSymbol === $symbol ? null : $symbol;
        $this->resolveSelection();
    }

    private function resolveSelection(): void
    {
        if ($this->selectedPileSymbol === null || $this->selectedHandSymbol === null) {
            return;
        }

        // Symbols must match
        if ($this->selectedPileSymbol !== $this->selectedHandSymbol) {
            $this->resetSelections();
            $this->dispatch('spotit-shake');

            return;
        }

        // Start animation for this player
        $this->isAnimating = true;
        $this->pendingMatchSymbol = $this->selectedPileSymbol;
        $this->dispatch('spotit-match');
    }

    /**
     * Called after match animation completes (from JS).
     */
    public function completeMatch(): void
    {
        if (! $this->isAnimating || $this->pendingMatchSymbol === null) {
            return;
        }

        $symbol = $this->pendingMatchSymbol;

        try {
            $this->processMatch($symbol);
        } finally {
            $this->isAnimating = false;
            $this->pendingMatchSymbol = null;
            $this->resetSelections();
        }
    }

    private function processMatch(string $symbol): void
    {
        $room = GameRoom::find($this->roomCode);

        if ($room === null) {
            return;
        }

        $result = $room->attemptMatch($this->playerId, $symbol, $symbol);

        if (! $result['success']) {
            // Match failed (someone else got it first) - shake
            $this->dispatch('spotit-shake');

            return;
        }

        $room->save();

        // Store pending state - don't update cards yet (fairness)
        $this->pendingPileCard = $result['newPileCard'];
        $this->pendingHandCard = $result['newHandCard'] ?? [];
        $this->pendingPlayers = array_map(fn ($p) => $p->toArray(), $room->players);
        $this->pendingCardsRemaining = $room->remainingCards();

        // Show overlay for THIS player too
        $this->showMatchOverlay = true;
        $this->overlayPlayerName = $this->playerName;
        $this->overlaySymbol = $symbol;
        $this->overlayPlayerId = $this->playerId;
        $this->overlayIsMe = true;

        // Dispatch event to trigger overlay animation
        $this->dispatch('spotit-show-overlay', [
            'playerId' => $this->playerId,
        ]);

        // Broadcast the match to other players
        broadcast(new PlayerMatchedCard(
            roomCode: $room->code,
            playerId: $this->playerId,
            playerName: $this->playerName,
            matchedSymbol: $symbol,
            newPileCard: $result['newPileCard'],
            newHandCard: $result['newHandCard'] ?? [],
            players: array_map(fn ($p) => $p->toArray(), $room->players),
            cardsRemaining: $room->remainingCards(),
        ))->toOthers();

        if ($result['isGameOver']) {
            $winner = $room->getWinner();
            broadcast(new GameEnded(
                roomCode: $room->code,
                winnerId: $result['winnerId'],
                winnerName: $winner?->name ?? 'Unknown',
                scoreboard: $room->getScoreboard(),
            ));
        }
    }

    private function resetSelections(): void
    {
        $this->selectedPileSymbol = null;
        $this->selectedHandSymbol = null;
    }

    #[On('echo:game.{roomCode},.player.joined')]
    public function onPlayerJoined(array $data): void
    {
        $this->players = $data['allPlayers'];
    }

    #[On('echo:game.{roomCode},.player.left')]
    public function onPlayerLeft(array $data): void
    {
        $this->players = $data['allPlayers'];
    }

    #[On('echo:game.{roomCode},.game.started')]
    public function onGameStarted(array $data): void
    {
        $room = GameRoom::find($this->roomCode);
        if ($room !== null) {
            $this->syncFromRoom($room);
        }
    }

    #[On('echo:game.{roomCode},.card.matched')]
    public function onCardMatched(array $data): void
    {
        // Store pending state - don't update cards yet (fairness)
        $this->pendingPileCard = $data['newPileCard'];
        $this->pendingHandCard = $data['newHandCard'] ?? [];
        $this->pendingPlayers = $data['players'];
        $this->pendingCardsRemaining = $data['cardsRemaining'];

        // Show overlay for this player
        $this->showMatchOverlay = true;
        $this->overlayPlayerName = $data['playerName'];
        $this->overlaySymbol = $data['matchedSymbol'];
        $this->overlayPlayerId = $data['playerId'];
        $this->overlayIsMe = false;

        // Dispatch event to trigger overlay animation
        $this->dispatch('spotit-show-overlay', [
            'playerId' => $data['playerId'],
        ]);

        $this->resetSelections();
    }

    /**
     * Called after overlay animation completes - reveals new cards for all players.
     */
    public function clearOverlay(): void
    {
        // Apply pending card state now
        if ($this->pendingPileCard !== []) {
            $this->pileCard = $this->pendingPileCard;
            $this->pendingPileCard = [];
        }

        $this->handCard = $this->pendingHandCard;
        $this->pendingHandCard = [];

        if ($this->pendingPlayers !== []) {
            $this->players = $this->pendingPlayers;
            $this->pendingPlayers = [];
        }

        if ($this->pendingCardsRemaining > 0) {
            $this->cardsRemaining = $this->pendingCardsRemaining;
            $this->pendingCardsRemaining = 0;
        }

        $this->syncRotations();

        // Clear overlay state
        $this->showMatchOverlay = false;
        $this->overlayPlayerName = null;
        $this->overlaySymbol = null;
        $this->overlayPlayerId = null;
        $this->overlayIsMe = false;
    }

    #[On('echo:game.{roomCode},.game.ended')]
    public function onGameEnded(array $data): void
    {
        $this->status = 'finished';
        $this->winnerId = $data['winnerId'];
        $this->winnerName = $data['winnerName'];
        $this->scoreboard = $data['scoreboard'];
    }

    public function leaveRoom(): void
    {
        $room = GameRoom::find($this->roomCode);

        if ($room !== null) {
            $room->removePlayer($this->playerId);

            if ($room->players !== []) {
                $room->save();

                broadcast(new PlayerLeftRoom(
                    roomCode: $room->code,
                    playerId: $this->playerId,
                    playerName: $this->playerName,
                    allPlayers: array_map(fn ($p) => $p->toArray(), $room->players),
                ))->toOthers();
            }
        }

        $this->redirect(route('multiplayer.lobby'), navigate: true);
    }

    public function playAgain(): void
    {
        $this->redirect(route('multiplayer.lobby'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.multiplayer-game-ui');
    }

    private function syncFromRoom(GameRoom $room): void
    {
        $this->hostId = $room->hostId;
        $this->isHost = $room->isHost($this->playerId);
        $this->status = $room->status->value;
        $this->players = array_map(fn ($p) => $p->toArray(), $room->players);
        $this->pileCard = $room->pileCard;
        $this->handCard = $room->handCard;
        $this->cardsRemaining = $room->remainingCards();
        $this->rotationSeed = $room->rotationSeed;
        $this->winnerId = $room->winnerId;
        $this->scoreboard = $room->getScoreboard();

        if ($room->winnerId !== null) {
            $winner = $room->getWinner();
            $this->winnerName = $winner?->name ?? 'Unknown';
        }

        $this->syncRotations();
    }

    private function syncRotations(): void
    {
        $this->pileRotations = $this->pileCard !== [] ? $this->rotationsForCard('pile', $this->pileCard) : [];
        $this->handRotations = $this->handCard !== [] ? $this->rotationsForCard('hand', $this->handCard) : [];
    }

    /**
     * @param  array<int, string>  $card
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
}
