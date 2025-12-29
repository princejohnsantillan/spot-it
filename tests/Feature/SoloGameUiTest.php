<?php

use App\Livewire\SoloGameUi;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('shakes and deselects when the selected symbols do not match', function () {
    Livewire::test(SoloGameUi::class)
        ->set('hasStarted', true)
        ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
        ->set('hand', [['💯', '💀', '🤔', '😍'], ['👍🏼', '🤣', '🎉', '😭']])
        ->set('handCard', ['👍🏼', '🤣', '🎉', '😭'])
        ->call('selectPileSymbol', '😂')
        ->call('selectHandSymbol', '🤣')
        ->assertSet('selectedPileSymbol', null)
        ->assertSet('selectedHandSymbol', null)
        ->assertDispatched('spotit-shake');
});

it('advances the game when the selected symbols match', function () {
    $nextCard = ['😊', '🙏', '🔥', '🥰'];
    $currentCard = ['😂', '🎉', '😭', '👍🏼'];
    $pileCard = ['😂', '😊', '🙏', '🔥'];

    Livewire::test(SoloGameUi::class)
        ->set('hasStarted', true)
        ->set('pileCard', $pileCard)
        ->set('hand', [$nextCard, $currentCard])
        ->set('handCard', $currentCard)
        ->set('pileCount', 1)
        ->call('selectPileSymbol', '😂')
        ->call('selectHandSymbol', '😂')
        ->assertSet('isAnimating', true)
        ->assertSet('pendingMatchSymbol', '😂')
        ->assertDispatched('spotit-match')
        ->assertSet('pileCard', $pileCard)
        ->assertSet('handCard', $currentCard)
        ->call('completeMatch')
        ->assertSet('pileCard', $currentCard)
        ->assertSet('handCard', $nextCard)
        ->assertSet('pileCount', 2)
        ->assertCount('hand', 1)
        ->assertSet('isAnimating', false)
        ->assertSet('pendingMatchSymbol', null)
        ->assertSet('selectedPileSymbol', null)
        ->assertSet('selectedHandSymbol', null);
});

it('starts with an empty state until New Game is clicked', function () {
    Livewire::test(SoloGameUi::class)
        ->assertSet('hasStarted', false)
        ->assertSet('pileCount', 0)
        ->assertSet('pileCard', [])
        ->assertSet('hand', [])
        ->assertSee('New Game');
});

it('shows the game duration when the game is finished', function () {
    Carbon::setTestNow(Carbon::parse('2020-01-01 00:01:30'));

    try {
        Livewire::test(SoloGameUi::class)
            ->set('hasStarted', true)
            ->set('startedAt', Carbon::now()->subSeconds(90)->timestamp)
            ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
            ->set('hand', [['😂', '🎉', '😭', '👍🏼']])
            ->set('handCard', ['😂', '🎉', '😭', '👍🏼'])
            ->set('pileCount', 1)
            ->call('selectPileSymbol', '😂')
            ->call('selectHandSymbol', '😂')
            ->call('completeMatch')
            ->assertSet('isOver', true)
            ->assertSet('finishedAt', Carbon::now()->timestamp)
            ->assertSee('Duration: 1 minute 30 seconds');
    } finally {
        Carbon::setTestNow();
    }
});

it('assigns stable, unique rotations for each symbol on a card', function () {
    $component = Livewire::test(SoloGameUi::class)->set('rotationSeed', 123);

    $card = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

    $first = $component->instance()->rotationsForCard('pile', $card);
    $second = $component->instance()->rotationsForCard('pile', $card);

    expect($first)->toBe($second);
    expect(array_values($first))->toHaveCount(8);
    expect(array_unique(array_values($first)))->toHaveCount(8);

    $component->set('rotationSeed', 456);
    $third = $component->instance()->rotationsForCard('pile', $card);

    expect($third)->not->toBe($first);
});
