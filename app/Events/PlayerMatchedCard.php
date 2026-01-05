<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PlayerMatchedCard implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<int, string>  $newPileCard
     * @param  array<int, string>  $newHandCard
     * @param  array<int, array<string, mixed>>  $players
     */
    public function __construct(
        public string $tableCode,
        public string $playerId,
        public string $playerName,
        public string $matchedSymbol,
        public array $newPileCard,
        public array $newHandCard,
        public array $players,
        public int $cardsRemaining,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('game.'.$this->tableCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'card.matched';
    }
}
