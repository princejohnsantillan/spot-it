<?php

declare(strict_types=1);

namespace App\Decks;

use App\Card;
use App\Contracts\Symbol;
use App\DeckGenerator;
use App\Symbols\EmojiSymbol;

final class EmojiDeck
{
    public const EMOJIS = [
        '😂', '😊', '🙏', '🔥', '😍', '🎉', '😭', '🥰', '👍', '🤣', '💯', '💀', '🤔',
        '😎', '😁', '😅', '🙌', '✨', '🤩', '😜', '😇', '🥳', '😡', '😱', '🤯', '🤗',
        '🤝', '👀', '💪', '🧠', '🐶', '🐱', '🦊', '🐻', '🐼', '🐸', '🐵', '🦄', '🐝',
        '🐢', '🐙', '🦋', '🌈', '⭐', '🌙', '☀️', '⚡', '🍕', '🍔', '🍟', '🍣', '🍩',
        '🍎', '⚽', '🏀', '🎮', '🎸',
    ];

    /**
     * @var Symbol[]
     */
    public array $symbols;

    public function __construct()
    {
        foreach (self::EMOJIS as $emoji) {
            $this->symbols[$emoji] = new EmojiSymbol($emoji);
        }
    }

    public function find(string $emoji): Symbol
    {
        return $this->symbols[$emoji];
    }

    /**
     * @return Card[]
     */
    public function generate(): array
    {
        $emojis = array_values(array_unique(self::EMOJIS));

        if (count($emojis) !== count(self::EMOJIS)) {
            throw new \LogicException('Emoji deck emojis must be unique.');
        }

        return (new DeckGenerator)
            ->setSymbols(array_values($this->symbols))
            ->generate();
    }
}
