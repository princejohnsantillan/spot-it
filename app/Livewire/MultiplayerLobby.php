<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Events\PlayerJoinedRoom;
use App\Multiplayer\GameRoom;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class MultiplayerLobby extends Component
{
    #[Validate('required|string|min:2|max:20')]
    public string $nickname = '';

    #[Validate('required|string|size:6|alpha_num')]
    public string $roomCode = '';

    public string $error = '';

    public function mount(): void
    {
        // Restore nickname from session if available
        $this->nickname = session('guest_player_name', '');
    }

    public function createRoom(): void
    {
        $this->validate([
            'nickname' => 'required|string|min:2|max:20',
        ]);

        $this->error = '';

        $playerId = $this->ensureGuestPlayer();
        session(['guest_player_name' => $this->nickname]);

        $room = GameRoom::create($playerId, $this->nickname);
        $room->save();

        $this->redirectToRoom($room->code);
    }

    public function joinRoom(): void
    {
        $this->validate();

        $this->error = '';
        $code = strtoupper($this->roomCode);

        $room = GameRoom::find($code);

        if ($room === null) {
            $this->error = 'Room not found. Check the code and try again.';

            return;
        }

        if ($room->status->value !== 'waiting') {
            $this->error = 'This game has already started.';

            return;
        }

        if (count($room->players) >= GameRoom::MAX_PLAYERS) {
            $this->error = 'This room is full.';

            return;
        }

        $playerId = $this->ensureGuestPlayer();
        session(['guest_player_name' => $this->nickname]);

        if (! $room->addPlayer($playerId, $this->nickname)) {
            $this->error = 'Could not join the room. Please try again.';

            return;
        }

        $room->save();

        // Broadcast to all players in the room
        broadcast(new PlayerJoinedRoom(
            roomCode: $room->code,
            player: $room->getPlayer($playerId)->toArray(),
            allPlayers: array_map(fn ($p) => $p->toArray(), $room->players),
        ));

        $this->redirectToRoom($room->code);
    }

    public function render(): View
    {
        return view('livewire.multiplayer-lobby');
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

    private function redirectToRoom(string $code): void
    {
        $this->redirect(route('multiplayer.room', ['code' => $code]), navigate: true);
    }
}
