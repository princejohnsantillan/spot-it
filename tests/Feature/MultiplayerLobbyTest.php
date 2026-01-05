<?php

declare(strict_types=1);

use App\Livewire\MultiplayerLobby;
use App\Multiplayer\GameTable;
use Livewire\Livewire;

beforeEach(function (): void {
    // Clear any existing game tables from cache
    cache()->flush();
});

it('renders the lobby page', function (): void {
    $this->get(route('multiplayer.lobby'))
        ->assertStatus(200)
        ->assertSeeLivewire(MultiplayerLobby::class);
});

it('creates a new table with valid nickname', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'TestPlayer')
        ->call('createTable')
        ->assertRedirect();

    // Verify table was created
    expect(session('guest_player_id'))->not->toBeNull();
    expect(session('guest_player_name'))->toBe('TestPlayer');
});

it('requires a nickname to create a table', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', '')
        ->call('createTable')
        ->assertHasErrors(['nickname']);
});

it('requires nickname to be at least 2 characters', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'A')
        ->call('createTable')
        ->assertHasErrors(['nickname']);
});

it('requires nickname to be at most 20 characters', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', str_repeat('A', 21))
        ->call('createTable')
        ->assertHasErrors(['nickname']);
});

it('shows error when joining non-existent table', function (): void {
    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'TestPlayer')
        ->set('tableCode', 'ABCDEF')
        ->call('joinTable')
        ->assertSet('error', 'Table not found. Check the code and try again.');
});

it('can join an existing table', function (): void {
    // Create a table first
    $hostId = 'host-id-123';
    $table = GameTable::create($hostId, 'HostPlayer');
    $table->save();

    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'JoiningPlayer')
        ->set('tableCode', $table->code)
        ->call('joinTable')
        ->assertRedirect(route('multiplayer.table', ['code' => $table->code]));
});

it('shows error when table is full', function (): void {
    $hostId = 'host-id-123';
    $table = GameTable::create($hostId, 'Host');

    // Fill up the table
    for ($i = 0; $i < GameTable::MAX_PLAYERS - 1; $i++) {
        $table->addPlayer("player-{$i}", "Player {$i}");
    }
    $table->save();

    Livewire::test(MultiplayerLobby::class)
        ->set('nickname', 'OverflowPlayer')
        ->set('tableCode', $table->code)
        ->call('joinTable')
        ->assertSet('error', 'This table is full.');
});
