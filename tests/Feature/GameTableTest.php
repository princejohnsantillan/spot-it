<?php

declare(strict_types=1);

use App\Multiplayer\GameStatus;
use App\Multiplayer\GameTable;

beforeEach(function (): void {
    cache()->flush();
});

/**
 * Helper to start the game (goes through countdown -> playing).
 */
function startGame(GameTable $table): bool
{
    if (! $table->startCountdown()) {
        return false;
    }

    return $table->start();
}

it('generates a unique 6-character alphanumeric code', function (): void {
    $table = GameTable::create('host-123', 'HostPlayer');

    expect($table->code)->toHaveLength(6);
    expect($table->code)->toMatch('/^[A-Z0-9]{6}$/');
});

it('creates a table with host as first player', function (): void {
    $table = GameTable::create('host-123', 'HostPlayer');

    expect($table->players)->toHaveCount(1);
    expect($table->isHost('host-123'))->toBeTrue();
    expect($table->getPlayer('host-123')->name)->toBe('HostPlayer');
});

it('starts with waiting status', function (): void {
    $table = GameTable::create('host-123', 'HostPlayer');

    expect($table->status)->toBe(GameStatus::Waiting);
});

it('can add players up to max limit', function (): void {
    $table = GameTable::create('host', 'Host');

    for ($i = 1; $i < GameTable::MAX_PLAYERS; $i++) {
        expect($table->addPlayer("player-{$i}", "Player {$i}"))->toBeTrue();
    }

    expect($table->players)->toHaveCount(GameTable::MAX_PLAYERS);
    expect($table->addPlayer('overflow', 'Overflow'))->toBeFalse();
});

it('cannot add players after game starts', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    expect($table->addPlayer('late-player', 'Late'))->toBeFalse();
});

it('can save and retrieve from cache', function (): void {
    $table = GameTable::create('host-123', 'HostPlayer');
    $table->save();

    $retrieved = GameTable::find($table->code);

    expect($retrieved)->not->toBeNull();
    expect($retrieved->code)->toBe($table->code);
    expect($retrieved->hostId)->toBe('host-123');
    expect($retrieved->players)->toHaveCount(1);
});

it('cannot start with less than minimum players', function (): void {
    $table = GameTable::create('host', 'Host');

    expect($table->canStart())->toBeFalse();
    expect($table->startCountdown())->toBeFalse();
});

it('can start with minimum players', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');

    expect($table->canStart())->toBeTrue();
    expect(startGame($table))->toBeTrue();
    expect($table->status)->toBe(GameStatus::Playing);
});

it('sets up shared pile and hand cards when game starts', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Both pile and hand should have 8 symbols
    expect($table->pileCard)->toHaveCount(8);
    expect($table->handCard)->toHaveCount(8);

    // Deck should have remaining cards (57 total - 2 drawn = 55)
    expect($table->deck)->toHaveCount(55);
});

it('validates match requires same symbol on pile and hand', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Find the actual matching symbol between pile and hand
    $matchingSymbol = null;
    foreach ($table->pileCard as $symbol) {
        if (in_array($symbol, $table->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    expect($matchingSymbol)->not->toBeNull();

    // Valid match - same symbol on both
    $result = $table->attemptMatch('host', $matchingSymbol, $matchingSymbol);
    expect($result['success'])->toBeTrue();
});

it('rejects match when symbols differ', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Try matching with different symbols
    $result = $table->attemptMatch('host', $table->pileCard[0], $table->handCard[1]);
    expect($result['success'])->toBeFalse();
});

it('rejects match when symbol not on both cards', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Find a symbol only on pile
    $pileOnlySymbol = null;
    foreach ($table->pileCard as $symbol) {
        if (! in_array($symbol, $table->handCard, true)) {
            $pileOnlySymbol = $symbol;
            break;
        }
    }

    if ($pileOnlySymbol !== null) {
        $result = $table->attemptMatch('host', $pileOnlySymbol, $pileOnlySymbol);
        expect($result['success'])->toBeFalse();
    }
});

it('updates pile and hand on successful match', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    $originalHand = $table->handCard;
    $originalDeckCount = count($table->deck);

    // Find matching symbol
    $matchingSymbol = null;
    foreach ($table->pileCard as $symbol) {
        if (in_array($symbol, $table->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    $result = $table->attemptMatch('host', $matchingSymbol, $matchingSymbol);

    expect($result['success'])->toBeTrue();
    expect($table->pileCard)->toBe($originalHand); // Hand becomes pile
    expect($table->deck)->toHaveCount($originalDeckCount - 1); // One card drawn
    expect($result['newPileCard'])->toBe($originalHand);
});

it('tracks player scores', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Find matching symbol and make a match
    $matchingSymbol = null;
    foreach ($table->pileCard as $symbol) {
        if (in_array($symbol, $table->handCard, true)) {
            $matchingSymbol = $symbol;
            break;
        }
    }

    $table->attemptMatch('host', $matchingSymbol, $matchingSymbol);

    expect($table->getPlayer('host')->score)->toBe(1);
    expect($table->getPlayer('player-2')->score)->toBe(0);
});

it('ends game when deck is empty after match', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Empty the deck by making all valid matches
    $iterations = 0;
    $maxIterations = 60; // Safety limit

    while ($table->status === GameStatus::Playing && $iterations < $maxIterations) {
        $matchingSymbol = null;
        foreach ($table->pileCard as $symbol) {
            if (in_array($symbol, $table->handCard, true)) {
                $matchingSymbol = $symbol;
                break;
            }
        }

        if ($matchingSymbol === null) {
            break;
        }

        // Alternate between players
        $playerId = $iterations % 2 === 0 ? 'host' : 'player-2';
        $table->attemptMatch($playerId, $matchingSymbol, $matchingSymbol);
        $iterations++;
    }

    expect($table->status)->toBe(GameStatus::Finished);
    expect($table->winnerId)->not->toBeNull();
});

it('determines winner by highest score', function (): void {
    $table = GameTable::create('host', 'Host');
    $table->addPlayer('player-2', 'Player 2');
    startGame($table);

    // Host makes all the matches
    while ($table->status === GameStatus::Playing) {
        $matchingSymbol = null;
        foreach ($table->pileCard as $symbol) {
            if (in_array($symbol, $table->handCard, true)) {
                $matchingSymbol = $symbol;
                break;
            }
        }

        if ($matchingSymbol === null) {
            break;
        }

        $table->attemptMatch('host', $matchingSymbol, $matchingSymbol);
    }

    expect($table->winnerId)->toBe('host');
    expect($table->getPlayer('host')->score)->toBeGreaterThan(0);
    expect($table->getPlayer('player-2')->score)->toBe(0);
});
