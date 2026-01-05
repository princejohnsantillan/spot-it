<?php

declare(strict_types=1);

use App\Livewire\MultiplayerLobby;
use App\Multiplayer\GameRoom;
use Livewire\Livewire;

beforeEach(function (): void {
    // Clear any existing game rooms from cache
    cache()->flush();
});

it('renders the lobby page', function (): void {
    $this->get(route('multiplayer.lobby'))
        ->assertStatus(200)
        ->assertSeeLivewire(MultiplayerLobby::class);
});

it('creates a new room with valid nickname', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'TestPlayer')
        ->call('createRoom')
        ->assertRedirect();

    // Verify room was created
    expect(session('guest_player_id'))->not->toBeNull();
    expect(session('guest_player_name'))->toBe('TestPlayer');
});

it('requires a nickname to create a room', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', '')
        ->call('createRoom')
        ->assertHasErrors(['nickname']);
});

it('requires nickname to be at least 2 characters', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'A')
        ->call('createRoom')
        ->assertHasErrors(['nickname']);
});

it('requires nickname to be at most 20 characters', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', str_repeat('A', 21))
        ->call('createRoom')
        ->assertHasErrors(['nickname']);
});

it('shows error when joining non-existent room', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'TestPlayer')
        ->set('roomCode', 'ABCDEF')
        ->call('joinRoom')
        ->assertSet('error', 'Room not found. Check the code and try again.');
});

it('can join an existing room', function (): void {
    // Create a room first
    $hostId = 'host-id-123';
    $room = GameRoom::create($hostId, 'HostPlayer');
    $room->save();

    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'JoiningPlayer')
        ->set('roomCode', $room->code)
        ->call('joinRoom')
        ->assertRedirect(route('multiplayer.room', ['code' => $room->code]));
});

it('shows error when room is full', function (): void {
    $hostId = 'host-id-123';
    $room = GameRoom::create($hostId, 'Host');

    // Fill up the room
    for ($i = 0; $i < GameRoom::MAX_PLAYERS - 1; $i++) {
        $room->addPlayer("player-{$i}", "Player {$i}");
    }
    $room->save();

    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'OverflowPlayer')
        ->set('roomCode', $room->code)
        ->call('joinRoom')
        ->assertSet('error', 'This room is full.');
});
