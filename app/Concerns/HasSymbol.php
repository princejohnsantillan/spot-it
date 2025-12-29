<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\Symbol;

/**
 * @mixin Symbol
 */
trait HasSymbol
{
    public function isSymbol(Symbol $symbol): bool
    {
        return $this->getId() === $symbol->getId();
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
