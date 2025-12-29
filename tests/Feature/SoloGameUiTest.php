<?php

use App\Livewire\SoloGameUi;
use Livewire\Livewire;

it('shakes and deselects when the selected symbols do not match', function () {
    Livewire::test(SoloGameUi::class)
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

    Livewire::test(SoloGameUi::class)
        ->set('pileCard', ['😂', '😊', '🙏', '🔥'])
        ->set('hand', [$nextCard, $currentCard])
        ->set('handCard', $currentCard)
        ->set('pileCount', 1)
        ->call('selectPileSymbol', '😂')
        ->call('selectHandSymbol', '😂')
        ->assertSet('pileCard', $currentCard)
        ->assertSet('handCard', $nextCard)
        ->assertSet('pileCount', 2)
        ->assertCount('hand', 1)
        ->assertSet('selectedPileSymbol', null)
        ->assertSet('selectedHandSymbol', null);
});
