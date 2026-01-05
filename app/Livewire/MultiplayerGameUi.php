<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Events\GameCountdownStarted;
use App\Events\GameEnded;
use App\Events\GameStarted;
use App\Events\PlayerLeftTable;
use App\Events\PlayerMatchedCard;
use App\Multiplayer\GameTable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class MultiplayerGameUi extends Component
{
    #[Locked]
    public string $tableCode = '';

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

    public int $countdown = 0;

    public function mount(string $code): void
    {
        $this->tableCode = strtoupper($code);
        $this->playerId = session('guest_player_id', '');
        $this->playerName = session('guest_player_name', '');

        if (! $this->playerId || ! $this->playerName) {
            $this->redirect(route('multiplayer.lobby'), navigate: true);

            return;
        }

        $table = GameTable::find($this->tableCode);

        if ($table === null) {
            $this->redirect(route('multiplayer.lobby'), navigate: true);

            return;
        }

        $this->syncFromTable($table);
    }

    public function startGame(): void
    {
        $table = GameTable::find($this->tableCode);

        if ($table === null || ! $table->isHost($this->playerId)) {
            return;
        }

        if (! $table->startCountdown()) {
            return;
        }

        $table->save();

        // Broadcast countdown started
        broadcast(new GameCountdownStarted(
            tableCode: $table->code,
            players: array_map(fn ($p) => $p->toArray(), $table->players),
            rotationSeed: $table->rotationSeed,
        ));

        $this->syncFromTable($table);
        $this->countdown = 5;
    }

    public function selectPileSymbol(string $symbol): void
    {
        if ($this->status !== 'playing') {
            return;
        }

        $this->selectedPileSymbol = $this->selectedPileSymbol === $symbol ? null : $symbol;
        $this->resolveSelection();
    }

    public function selectHandSymbol(string $symbol): void
    {
        if ($this->status !== 'playing') {
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

        // Process match immediately (no animation delay)
        $this->processMatch($this->selectedPileSymbol);
        $this->resetSelections();
    }

    private function processMatch(string $symbol): void
    {
        $table = GameTable::find($this->tableCode);

        if ($table === null) {
            return;
        }

        $result = $table->attemptMatch($this->playerId, $symbol, $symbol);

        if (! $result['success']) {
            // Match failed (someone else got it first) - shake
            $this->dispatch('spotit-shake');

            return;
        }

        $table->save();

        // Update cards immediately
        $this->pileCard = $result['newPileCard'];
        $this->handCard = $result['newHandCard'] ?? [];
        $this->players = array_map(fn ($p) => $p->toArray(), $table->players);
        $this->cardsRemaining = $table->remainingCards();
        $this->syncRotations();

        // Add to match history and trigger score pulse
        $this->addToMatchHistory($this->playerName, $symbol, true);
        $this->lastScoringPlayerId = $this->playerId;
        $this->dispatch('spotit-score-pulse', ['playerId' => $this->playerId]);

        // Broadcast the match to all players
        broadcast(new PlayerMatchedCard(
            tableCode: $table->code,
            playerId: $this->playerId,
            playerName: $this->playerName,
            matchedSymbol: $symbol,
            newPileCard: $result['newPileCard'],
            newHandCard: $result['newHandCard'] ?? [],
            players: array_map(fn ($p) => $p->toArray(), $table->players),
            cardsRemaining: $table->remainingCards(),
        ));

        if ($result['isGameOver']) {
            $winner = $table->getWinner();
            broadcast(new GameEnded(
                tableCode: $table->code,
                winnerId: $result['winnerId'],
                winnerName: $winner?->name ?? 'Unknown',
                scoreboard: $table->getScoreboard(),
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
        $this->selectedPileSymbol = null;
        $this->selectedHandSymbol = null;
    }

    #[On('echo:game.{tableCode},.player.joined')]
    public function onPlayerJoined(array $data): void
    {
        // Ignore own join event (we're redirecting anyway)
        if (($data['player']['id'] ?? null) === $this->playerId) {
            return;
        }

        $this->players = $data['allPlayers'];
    }

    #[On('echo:game.{tableCode},.player.left')]
    public function onPlayerLeft(array $data): void
    {
        // Ignore own leave event (we're redirecting anyway)
        if (($data['playerId'] ?? null) === $this->playerId) {
            return;
        }

        $this->players = $data['allPlayers'];
    }

    #[On('echo:game.{tableCode},.game.started')]
    public function onGameStarted(array $data): void
    {
        $table = GameTable::find($this->tableCode);
        if ($table !== null) {
            $this->syncFromTable($table);
        }
    }

    #[On('echo:game.{tableCode},.card.matched')]
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

    #[On('echo:game.{tableCode},.game.ended')]
    public function onGameEnded(array $data): void
    {
        $this->status = 'finished';
        $this->winnerId = $data['winnerId'];
        $this->winnerName = $data['winnerName'];
        $this->scoreboard = $data['scoreboard'];
    }

    #[On('echo:game.{tableCode},.game.countdown.started')]
    public function onCountdownStarted(array $data): void
    {
        $table = GameTable::find($this->tableCode);
        if ($table !== null) {
            $this->syncFromTable($table);
            $this->countdown = 5;
        }
    }

    public function startGameAfterCountdown(): void
    {
        $table = GameTable::find($this->tableCode);

        if ($table === null || ! $table->isHost($this->playerId)) {
            return;
        }

        if (! $table->start()) {
            return;
        }

        $table->save();

        // Broadcast game started
        broadcast(new GameStarted(
            tableCode: $table->code,
            pileCard: $table->pileCard,
            players: array_map(fn ($p) => $p->toArray(), $table->players),
            rotationSeed: $table->rotationSeed,
        ));

        $this->syncFromTable($table);
    }

    public function leaveTable(): void
    {
        $table = GameTable::find($this->tableCode);

        if ($table !== null) {
            $table->removePlayer($this->playerId);

            if ($table->players !== []) {
                $table->save();

                broadcast(new PlayerLeftTable(
                    tableCode: $table->code,
                    playerId: $this->playerId,
                    playerName: $this->playerName,
                    allPlayers: array_map(fn ($p) => $p->toArray(), $table->players),
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

    private function syncFromTable(GameTable $table): void
    {
        $this->hostId = $table->hostId;
        $this->isHost = $table->isHost($this->playerId);
        $this->status = $table->status->value;
        $this->players = array_map(fn ($p) => $p->toArray(), $table->players);
        $this->pileCard = $table->pileCard;
        $this->handCard = $table->handCard;
        $this->cardsRemaining = $table->remainingCards();
        $this->rotationSeed = $table->rotationSeed;
        $this->winnerId = $table->winnerId;
        $this->scoreboard = $table->getScoreboard();

        if ($table->winnerId !== null) {
            $winner = $table->getWinner();
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
