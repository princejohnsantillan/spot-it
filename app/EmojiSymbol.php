<?php

declare(strict_types=1);

namespace App;

use App\Contracts\Symbol;

final class EmojiSymbol implements Contracts\Symbol
{
    public function __construct(private string $emoji)
    {
    }

    public function getId(): string
    {
        return $this->emoji;
    }

    public function render(): string
    {
        return $this->emoji;
    }

    public function isSymbol(Symbol $symbol): bool
    {
        return $this->getId() === $symbol->getId();
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
