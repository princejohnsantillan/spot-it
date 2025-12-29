<?php

declare(strict_types=1);

namespace App\Decks;

use App\Card;
use App\DeckGenerator;
use App\Symbols\EmojiSymbol;

final class EmojiDeck
{
    public const EMOJIS = [
        '😂', '😊', '🙏', '🔥', '😍', '🎉', '😭', '🥰', '👍🏼', '🤣', '💯', '💀', '🤔',
    ];

    /**
     * @return Card[]
     */
    public static function generate(): array
    {
        $symbols = [];

        foreach (self::EMOJIS as $emoji) {
            $symbols[] = new EmojiSymbol($emoji);
        }

        return (new DeckGenerator(3))->setSymbols($symbols)->generate();
    }
}
