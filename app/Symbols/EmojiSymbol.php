<?php

declare(strict_types=1);

namespace App\Symbols;

use App\Concerns\HasSymbol;
use App\Contracts;

final class EmojiSymbol implements Contracts\Symbol
{
    use HasSymbol;

    public function __construct(
        private string $emoji
    ) {}

    public function getId(): string
    {
        return $this->emoji;
    }

    public function render(): string
    {
        return $this->emoji;
    }
}
