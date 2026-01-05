<?php

declare(strict_types=1);

namespace App\Multiplayer;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final class GuestPlayer implements Arrayable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public int $score = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'score' => $this->score,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            score: $data['score'] ?? 0,
        );
    }
}
