<?php

declare(strict_types=1);

use App\Multiplayer\GameRoom;
use App\Multiplayer\GameStatus;

beforeEach(function (): void {
    cache()->flush();
});

it('generates a unique 6-character code', function (): void {
    $room = GameRoom::create('host-123', 'HostPlayer');

    expect($room->code)->toHaveLength(6);
    expect($room->code)->toMatch('/^[A-Z]{6}$/');
});

it('creates a room with host as first player', function (): void {
    $room = GameRoom::create('host-123', 'HostPlayer');

    expect($room->players)->toHaveCount(1);
    expect($room->isHost('host-123'))->toBeTrue();
    expect($room->getPlayer('host-123')->name)->toBe('HostPlayer');
});

it('starts with waiting status', function (): void {
    $room = GameRoom::create('host-123', 'HostPlayer');

    expect($room->status)->toBe(GameStatus::Waiting);
});

it('can add players up to max limit', function (): void {
    $room = GameRoom::create('host', 'Host');

    for ($i = 1; $i < GameRoom::MAX_PLAYERS; $i++) {
        expect($room->addPlayer("player-{$i}", "Player {$i}"))->toBeTrue();
    }

    expect($room->players)->toHaveCount(GameRoom::MAX_PLAYERS);
    expect($room->addPlayer('overflow', 'Overflow'))->toBeFalse();
});

it('cannot add players after game starts', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    expect($room->addPlayer('late-player', 'Late'))->toBeFalse();
});

it('can save and retrieve from cache', function (): void {
    $room = GameRoom::create('host-123', 'HostPlayer');
    $room->save();

    $retrieved = GameRoom::find($room->code);

    expect($retrieved)->not->toBeNull();
    expect($retrieved->code)->toBe($room->code);
    expect($retrieved->hostId)->toBe('host-123');
    expect($retrieved->players)->toHaveCount(1);
});

it('cannot start with less than minimum players', function (): void {
    $room = GameRoom::create('host', 'Host');

    expect($room->canStart())->toBeFalse();
    expect($room->start())->toBeFalse();
});

it('can start with minimum players', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');

    expect($room->canStart())->toBeTrue();
    expect($room->start())->toBeTrue();
    expect($room->status)->toBe(GameStatus::Playing);
});

it('sets up shared pile and hand cards when game starts', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Both pile and hand should have 8 symbols
    expect($room->pileCard)->toHaveCount(8);
    expect($room->handCard)->toHaveCount(8);

    // Deck should have remaining cards (57 total - 2 drawn = 55)
    expect($room->deck)->toHaveCount(55);
});

it('validates match requires same symbol on pile and hand', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Find the actual matching symbol between pile and hand
    $matchingSymbol = null;
    foreach ($room->pileCard as $symbol) {
        if (in_array($symbol, $room->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    expect($matchingSymbol)->not->toBeNull();

    // Valid match - same symbol on both
    $result = $room->attemptMatch('host', $matchingSymbol, $matchingSymbol);
    expect($result['success'])->toBeTrue();
});

it('rejects match when symbols differ', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Try matching with different symbols
    $result = $room->attemptMatch('host', $room->pileCard[0], $room->handCard[1]);
    expect($result['success'])->toBeFalse();
});

it('rejects match when symbol not on both cards', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Find a symbol only on pile
    $pileOnlySymbol = null;
    foreach ($room->pileCard as $symbol) {
        if (! in_array($symbol, $room->handCard, true)) {
            $pileOnlySymbol = $symbol;
            break;
        }
    }

    if ($pileOnlySymbol !== null) {
        $result = $room->attemptMatch('host', $pileOnlySymbol, $pileOnlySymbol);
        expect($result['success'])->toBeFalse();
    }
});

it('updates pile and hand on successful match', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    $originalHand = $room->handCard;
    $originalDeckCount = count($room->deck);

    // Find matching symbol
    $matchingSymbol = null;
    foreach ($room->pileCard as $symbol) {
        if (in_array($symbol, $room->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    $result = $room->attemptMatch('host', $matchingSymbol, $matchingSymbol);

    expect($result['success'])->toBeTrue();
    expect($room->pileCard)->toBe($originalHand); // Hand becomes pile
    expect($room->deck)->toHaveCount($originalDeckCount - 1); // One card drawn
    expect($result['newPileCard'])->toBe($originalHand);
});

it('tracks player scores', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Find matching symbol and make a match
    $matchingSymbol = null;
    foreach ($room->pileCard as $symbol) {
        if (in_array($symbol, $room->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    $room->attemptMatch('host', $matchingSymbol, $matchingSymbol);

    expect($room->getPlayer('host')->score)->toBe(1);
    expect($room->getPlayer('player-2')->score)->toBe(0);
});

it('ends game when deck is empty after match', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Empty the deck by making all valid matches
    $iterations = 0;
    $maxIterations = 60; // Safety limit

    while ($room->status === GameStatus::Playing && $iterations < $maxIterations) {
        $matchingSymbol = null;
        foreach ($room->pileCard as $symbol) {
            if (in_array($symbol, $room->handCard, true)) {
                $matchingSymbol = $symbol;
                break;
            }
        }

        if ($matchingSymbol === null) {
            break;
        }

        // Alternate between players
        $playerId = $iterations % 2 === 0 ? 'host' : 'player-2';
        $room->attemptMatch($playerId, $matchingSymbol, $matchingSymbol);
        $iterations++;
    }

    expect($room->status)->toBe(GameStatus::Finished);
    expect($room->winnerId)->not->toBeNull();
});

it('determines winner by highest score', function (): void {
    $room = GameRoom::create('host', 'Host');
    $room->addPlayer('player-2', 'Player 2');
    $room->start();

    // Host makes all the matches
    while ($room->status === GameStatus::Playing) {
        $matchingSymbol = null;
        foreach ($room->pileCard as $symbol) {
            if (in_array($symbol, $room->handCard, true)) {
                $matchingSymbol = $symbol;
                break;
            }
        }

        if ($matchingSymbol === null) {
            break;
        }

        $room->attemptMatch('host', $matchingSymbol, $matchingSymbol);
    }

    expect($room->winnerId)->toBe('host');
    expect($room->getPlayer('host')->score)->toBeGreaterThan(0);
    expect($room->getPlayer('player-2')->score)->toBe(0);
});
