<?php

namespace App\Contracts;

interface Symbol
{
    public function getId(): string;

    public function render(): string;

    public function isSymbol(Symbol $symbol): bool;

    public function __toString(): string;
}
