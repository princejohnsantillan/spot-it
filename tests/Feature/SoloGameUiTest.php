<?php

use App\Livewire\SoloGameUi;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('shakes when the dragged symbols do not match', function () {
    $component = Livewire::test(SoloGameUi::class);

    session()->put("spotit.solo.{$component->instance()->gameKey}.hand", [['💯', '💀', '🤔', '😍'], ['👍🏼', '🤣', '🎉', '😭']]);

    $component
        ->set('hasStarted', true)
        ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
        ->set('handCard', ['👍🏼', '🤣', '🎉', '😭'])
        ->set('handRemaining', 2)
        ->call('attemptMatch', '😂', '🤣')
        ->assertDispatched('spotit-shake');
});

it('advances the game when the dragged symbols match', function () {
    $nextCard = ['😊', '🙏', '🔥', '🥰'];
    $currentCard = ['😂', '🎉', '😭', '👍🏼'];
    $pileCard = ['😂', '😊', '🙏', '🔥'];

    $component = Livewire::test(SoloGameUi::class);

    session()->put("spotit.solo.{$component->instance()->gameKey}.hand", [$nextCard, $currentCard]);

    $component
        ->set('hasStarted', true)
        ->set('pileCard', $pileCard)
        ->set('handCard', $currentCard)
        ->set('handRemaining', 2)
        ->set('pileCount', 1)
        ->call('attemptMatch', '😂', '😂')
        ->assertSet('isAnimating', true)
        ->assertSet('pendingMatchSymbol', '😂')
        ->assertSet('pendingPileSymbol', '😂')
        ->assertDispatched('spotit-match')
        ->assertSet('pileCard', $pileCard)
        ->assertSet('handCard', $currentCard)
        ->call('completeMatch')
        ->assertSet('pileCard', $currentCard)
        ->assertSet('handCard', $nextCard)
        ->assertSet('handRemaining', 1)
        ->assertSet('pileCount', 2)
        ->assertSet('isAnimating', false)
        ->assertSet('pendingMatchSymbol', null)
        ->assertSet('pendingPileSymbol', null);
});

it('starts with an empty state until New Game is clicked', function () {
    Livewire::test(SoloGameUi::class)
        ->assertSet('hasStarted', false)
        ->assertSet('pileCount', 0)
        ->assertSet('pileCard', [])
        ->assertSet('handRemaining', 0)
        ->assertSet('handCard', [])
        ->assertSee('New Game');
});

it('shows the game duration when the game is finished', function () {
    Carbon::setTestNow(Carbon::parse('2020-01-01 00:01:30'));

    try {
        $component = Livewire::test(SoloGameUi::class);

        session()->put("spotit.solo.{$component->instance()->gameKey}.hand", [['😂', '🎉', '😭', '👍🏼']]);

        $component
            ->set('hasStarted', true)
            ->set('startedAt', Carbon::now()->subSeconds(90)->timestamp)
            ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
            ->set('handCard', ['😂', '🎉', '😭', '👍🏼'])
            ->set('handRemaining', 1)
            ->set('pileCount', 1)
            ->call('attemptMatch', '😂', '😂')
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
    expect(min(array_values($first)))->toBeGreaterThanOrEqual(0);
    expect(max(array_values($first)))->toBeLessThanOrEqual(360);

    $component->set('rotationSeed', 456);
    $third = $component->instance()->rotationsForCard('pile', $card);

    expect($third)->not->toBe($first);
});

it('shakes when symbols do not exist on the cards', function () {
    $component = Livewire::test(SoloGameUi::class);

    session()->put("spotit.solo.{$component->instance()->gameKey}.hand", [['👍🏼', '🤣', '🎉', '😭']]);

    $component
        ->set('hasStarted', true)
        ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
        ->set('handCard', ['👍🏼', '🤣', '🎉', '😭'])
        ->set('handRemaining', 1)
        ->call('attemptMatch', '🍕', '🍕')
        ->assertDispatched('spotit-shake');
});

it('does not allow matching when game is animating', function () {
    $component = Livewire::test(SoloGameUi::class);

    session()->put("spotit.solo.{$component->instance()->gameKey}.hand", [['😂', '🎉', '😭', '👍🏼']]);

    $component
        ->set('hasStarted', true)
        ->set('isAnimating', true)
        ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
        ->set('handCard', ['😂', '🎉', '😭', '👍🏼'])
        ->set('handRemaining', 1)
        ->call('attemptMatch', '😂', '😂')
        ->assertNotDispatched('spotit-match')
        ->assertNotDispatched('spotit-shake');
});
