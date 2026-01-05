<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Events\PlayerJoinedTable;
use App\Multiplayer\GameTable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class MultiplayerJoin extends Component
{
    #[Locked]
    public string $tableCode = '';

    #[Validate('required|string|min:2|max:20')]
    public string $nickname = '';

    public string $error = '';

    public bool $tableExists = false;

    public string $tableStatus = '';

    public int $playerCount = 0;

    public function mount(string $code): void
    {
        $this->tableCode = strtoupper($code);
        $this->nickname = session('guest_player_name', '');

        $table = GameTable::find($this->tableCode);

        if ($table === null) {
            $this->tableExists = false;

            return;
        }

        $this->tableExists = true;
        $this->tableStatus = $table->status->value;
        $this->playerCount = count($table->players);

        // If user is already at this table, redirect them there
        $playerId = session('guest_player_id');
        if ($playerId && $table->getPlayer($playerId) !== null) {
            $this->redirect(route('multiplayer.table', ['code' => $this->tableCode]), navigate: true);
        }
    }

    public function joinTable(): void
    {
        $this->validate();

        $this->error = '';

        $table = GameTable::find($this->tableCode);

        if ($table === null) {
            $this->error = 'Table not found.';
            $this->tableExists = false;

            return;
        }

        if ($table->status->value !== 'waiting') {
            $this->error = 'This game has already started.';

            return;
        }

        if (count($table->players) >= GameTable::MAX_PLAYERS) {
            $this->error = 'This table is full.';

            return;
        }

        $playerId = $this->ensureGuestPlayer();
        session(['guest_player_name' => $this->nickname]);

        if (! $table->addPlayer($playerId, $this->nickname)) {
            $this->error = 'Could not join the table. Please try again.';

            return;
        }

        $table->save();

        // Broadcast to all players at the table
        broadcast(new PlayerJoinedTable(
            tableCode: $table->code,
            player: $table->getPlayer($playerId)->toArray(),
            allPlayers: array_map(fn ($p) => $p->toArray(), $table->players),
        ));

        $this->redirect(route('multiplayer.table', ['code' => $table->code]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.multiplayer-join');
    }

    private function ensureGuestPlayer(): string
    {
        $playerId = session('guest_player_id');

        if (! $playerId) {
            $playerId = (string) Str::uuid();
            session(['guest_player_id' => $playerId]);
        }

        return $playerId;
    }
}
