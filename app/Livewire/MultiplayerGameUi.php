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

    public ?string $pendingPileSymbol = null;

    public ?string $pendingHandSymbol = null;

    /**
     * Match history log
     *
     * @var array<int, array{playerName: string, symbol: string, isMe: bool}>
     */
    public array $matchHistory = [];

    public ?string $lastScoringPlayerId = null;

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

    public function attemptMatch(string $pileSymbol, string $handSymbol): void
    {
        if ($this->status !== 'playing') {
            return;
        }

        // Symbols must match
        if ($pileSymbol !== $handSymbol) {
            $this->dispatch('spotit-shake');

            return;
        }

        // Verify both symbols exist on their respective cards
        if (! in_array($pileSymbol, $this->pileCard, true) || ! in_array($handSymbol, $this->handCard, true)) {
            $this->dispatch('spotit-shake');

            return;
        }

        // Store pending symbols for animation highlighting
        $this->pendingPileSymbol = $pileSymbol;
        $this->pendingHandSymbol = $handSymbol;

        // Process match immediately (no animation delay in multiplayer)
        $this->processMatch($pileSymbol);

        // Reset pending symbols after processing
        $this->pendingPileSymbol = null;
        $this->pendingHandSymbol = null;
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

        // Update cards immediately
        $this->pileCard = $result['newPileCard'];
        $this->handCard = $result['newHandCard'] ?? [];
        $this->players = array_map(fn ($p) => $p->toArray(), $room->players);
        $this->cardsRemaining = $room->remainingCards();
        $this->syncRotations();

        // Add to match history and trigger score pulse
        $this->addToMatchHistory($this->playerName, $symbol, true);
        $this->lastScoringPlayerId = $this->playerId;
        $this->dispatch('spotit-score-pulse', ['playerId' => $this->playerId]);

        // Broadcast the match to all players
        broadcast(new PlayerMatchedCard(
            roomCode: $room->code,
            playerId: $this->playerId,
            playerName: $this->playerName,
            matchedSymbol: $symbol,
            newPileCard: $result['newPileCard'],
            newHandCard: $result['newHandCard'] ?? [],
            players: array_map(fn ($p) => $p->toArray(), $room->players),
            cardsRemaining: $room->remainingCards(),
        ));

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

    private function addToMatchHistory(string $playerName, string $symbol, bool $isMe): void
    {
        // Prepend to history (newest first), limit to 10 entries
        array_unshift($this->matchHistory, [
            'playerName' => $playerName,
            'symbol' => $symbol,
            'isMe' => $isMe,
        ]);

        if (count($this->matchHistory) > 10) {
            $this->matchHistory = array_slice($this->matchHistory, 0, 10);
        }
    }

    private function resetSelections(): void
    {
        $this->pendingPileSymbol = null;
        $this->pendingHandSymbol = null;
    }

    #[On('echo:game.{roomCode},.player.joined')]
    public function onPlayerJoined(array $data): void
    {
        // Ignore own join event (we're redirecting anyway)
        if (($data['player']['id'] ?? null) === $this->playerId) {
            return;
        }

        $this->players = $data['allPlayers'];
    }

    #[On('echo:game.{roomCode},.player.left')]
    public function onPlayerLeft(array $data): void
    {
        // Ignore own leave event (we're redirecting anyway)
        if (($data['playerId'] ?? null) === $this->playerId) {
            return;
        }

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
        // Ignore if this is our own match (we already updated from processMatch)
        if ($data['playerId'] === $this->playerId) {
            return;
        }

        // Update cards immediately
        $this->pileCard = $data['newPileCard'];
        $this->handCard = $data['newHandCard'] ?? [];
        $this->players = $data['players'];
        $this->cardsRemaining = $data['cardsRemaining'];
        $this->syncRotations();

        // Add to match history and trigger score pulse
        $this->addToMatchHistory($data['playerName'], $data['matchedSymbol'], false);
        $this->lastScoringPlayerId = $data['playerId'];
        $this->dispatch('spotit-score-pulse', ['playerId' => $data['playerId']]);

        $this->resetSelections();
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
                ));
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
