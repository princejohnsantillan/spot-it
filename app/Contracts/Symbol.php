<?php

namespace App\Contracts;

interface Symbol
{
    /**
     * Unique identifier of the symbol.
     */
    public function getId(): string;

    /**
     * Define how the symbol is to be rendered on the UI.
     */
    public function render(): string;

    public function isSymbol(Symbol $symbol): bool;

    /**
     * Alias for getId but used for casting the Symbol object to string.
     * The symbol object will be cast to string when checking for uniqueness.
     */
    public function __toString(): string;
}
