<?php

use App\DeckGenerator;
use App\Symbols\EmojiSymbol;

test('generates correct number of cards', function () {
    $order = 2;
    $generator = new DeckGenerator($order);

    $symbols = [];
    for ($i = 0; $i < $generator->getCount(); $i++) {
        $symbols[] = new EmojiSymbol("symbol_{$i}");
    }

    $generator->setSymbols($symbols);
    $deck = $generator->generate();

    expect($deck)->toHaveCount($generator->getCount());
});

test('each card has correct number of symbols', function () {
    $order = 2;
    $generator = new DeckGenerator($order);

    $symbols = [];
    for ($i = 0; $i < $generator->getCount(); $i++) {
        $symbols[] = new EmojiSymbol("symbol_{$i}");
    }

    $generator->setSymbols($symbols);
    $deck = $generator->generate();

    foreach ($deck as $card) {
        expect($card->getSymbols())->toHaveCount($generator->getPerCard());
    }
});

test('any two cards share exactly one symbol', function () {
    $order = 2;
    $generator = new DeckGenerator($order);

    $symbols = [];
    for ($i = 0; $i < $generator->getCount(); $i++) {
        $symbols[] = new EmojiSymbol("symbol_{$i}");
    }

    $generator->setSymbols($symbols);
    $deck = $generator->generate();

    // Check all pairs of cards
    for ($i = 0; $i < count($deck); $i++) {
        for ($j = $i + 1; $j < count($deck); $j++) {
            $commonSymbol = $deck[$i]->spotItSymbol($deck[$j]);

            expect($deck[$i]->contains($commonSymbol))->toBe(true);
            expect($deck[$j]->contains($commonSymbol))->toBe(true);
        }
    }
});

test('works with larger deck of order 3', function () {
    $order = 3;
    $generator = new DeckGenerator($order);

    $symbols = [];
    for ($i = 0; $i < $generator->getCount(); $i++) {
        $symbols[] = new EmojiSymbol("symbol_{$i}");
    }

    $generator->setSymbols($symbols);
    $deck = $generator->generate();

    expect($deck)->toHaveCount(13); // 3² + 3 + 1 = 13
    expect($deck[0]->getSymbols())->toHaveCount(4); // 3 + 1 = 4

    // Verify the spot-it property for a sample of pairs
    $commonSymbol = $deck[0]->spotItSymbol($deck[1]);
    expect($commonSymbol)->not->toBeFalse();

    $commonSymbol = $deck[5]->spotItSymbol($deck[10]);
    expect($commonSymbol)->not->toBeFalse();
});

test('works with default order of 7', function () {
    $generator = new DeckGenerator;

    expect($generator->getOrder())->toBe(7);
    expect($generator->getCount())->toBe(57); // 7² + 7 + 1 = 57
    expect($generator->getPerCard())->toBe(8); // 7 + 1 = 8

    $symbols = [];
    for ($i = 0; $i < $generator->getCount(); $i++) {
        $symbols[] = new EmojiSymbol("emoji_{$i}");
    }

    $generator->setSymbols($symbols);
    $deck = $generator->generate();

    expect($deck)->toHaveCount(57);
    expect($deck[0]->getSymbols())->toHaveCount(8);
});

test('throws exception when symbols not set', function () {
    $generator = new DeckGenerator(2);
    $generator->generate();
})->throws(\LogicException::class, 'Symbols must be set before generating a deck.');

test('throws exception when wrong number of symbols provided', function () {
    $generator = new DeckGenerator(2);
    $symbols = [new EmojiSymbol('a'), new EmojiSymbol('b')]; // Only 2 symbols, needs 7
    $generator->setSymbols($symbols);
})->throws(\LogicException::class);
