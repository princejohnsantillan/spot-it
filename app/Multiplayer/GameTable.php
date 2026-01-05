<?php

declare(strict_types=1);

namespace App\Multiplayer;

use App\Card;
use App\Dealer;
use App\Decks\EmojiDeck;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @implements Arrayable<string, mixed>
 */
final class GameTable implements Arrayable
{
    public const MIN_PLAYERS = 2;

    public const MAX_PLAYERS = 8;

    public const CACHE_TTL_MINUTES = 60;

    /**
     * @param  array<string, GuestPlayer>  $players
     * @param  array<int, string>  $pileCard  Current pile card (8 symbols)
     * @param  array<int, string>  $handCard  Current shared hand card (8 symbols)
     * @param  array<int, array<int, string>>  $deck  Remaining deck of cards
     */
    public function __construct(
        public readonly string $code,
        public readonly string $hostId,
        public GameStatus $status = GameStatus::Waiting,
        public array $players = [],
        public array $pileCard = [],
        public array $handCard = [],
        public array $deck = [],
        public int $rotationSeed = 0,
        public ?string $winnerId = null,
        public ?int $startedAt = null,
        public ?int $finishedAt = null,
    ) {}

    public static function create(string $hostId, string $hostName): self
    {
        $table = new self(
            code: self::generateCode(),
            hostId: $hostId,
        );

        $table->addPlayer($hostId, $hostName);

        return $table;
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::find($code) !== null);

        return $code;
    }

    public static function cacheKey(string $code): string
    {
        return "spotit.multiplayer.table.{$code}";
    }

    public static function find(string $code): ?self
    {
        $data = Cache::get(self::cacheKey($code));

        if ($data === null) {
            return null;
        }

        return self::fromArray($data);
    }

    public function save(): self
    {
        Cache::put(
            self::cacheKey($this->code),
            $this->toArray(),
            now()->addMinutes(self::CACHE_TTL_MINUTES)
        );

        return $this;
    }

    public function delete(): void
    {
        Cache::forget(self::cacheKey($this->code));
    }

    public function addPlayer(string $id, string $name): bool
    {
        if ($this->status !== GameStatus::Waiting) {
            return false;
        }

        if (count($this->players) >= self::MAX_PLAYERS) {
            return false;
        }

        if (isset($this->players[$id])) {
            return true; // Already at table
        }

        $this->players[$id] = new GuestPlayer($id, $name);

        return true;
    }

    public function removePlayer(string $id): void
    {
        unset($this->players[$id]);

        // If host leaves and game hasn't started, promote next player or delete table
        if ($id === $this->hostId && $this->status === GameStatus::Waiting) {
            if ($this->players === []) {
                $this->delete();
            }
        }
    }

    public function getPlayer(string $id): ?GuestPlayer
    {
        return $this->players[$id] ?? null;
    }

    public function isHost(string $id): bool
    {
        return $this->hostId === $id;
    }

    public function canStart(): bool
    {
        return $this->status === GameStatus::Waiting
            && count($this->players) >= self::MIN_PLAYERS;
    }

    public function startCountdown(): bool
    {
        if (! $this->canStart()) {
            return false;
        }

        $this->status = GameStatus::Countdown;
        $this->rotationSeed = random_int(1, PHP_INT_MAX);

        // Reset player scores
        foreach ($this->players as $player) {
            $player->score = 0;
        }

        return true;
    }

    public function start(): bool
    {
        if ($this->status !== GameStatus::Countdown) {
            return false;
        }

        $this->status = GameStatus::Playing;
        $this->startedAt = time();

        $deckCards = (new EmojiDeck)->generate();
        $dealer = Dealer::using($deckCards)->shuffle();

        // First card goes to pile
        $this->pileCard = $this->serializeCard($dealer->top());

        // Second card is the shared hand card
        $this->handCard = $this->serializeCard($dealer->top());

        // Rest of the deck is stored for future draws
        $this->deck = array_map(
            fn (Card $card): array => $this->serializeCard($card),
            $deckCards
        );

        return true;
    }

    /**
     * Get remaining cards count (deck + current hand card).
     */
    public function remainingCards(): int
    {
        $count = count($this->deck);
        if ($this->handCard !== []) {
            $count++;
        }

        return $count;
    }

    /**
     * Attempt to match the pile and hand cards with the given symbol.
     *
     * @return array{success: bool, newPileCard: array<int, string>|null, newHandCard: array<int, string>|null, isGameOver: bool, winnerId: string|null}
     */
    public function attemptMatch(string $playerId, string $pileSymbol, string $handSymbol): array
    {
        $result = [
            'success' => false,
            'newPileCard' => null,
            'newHandCard' => null,
            'isGameOver' => false,
            'winnerId' => null,
        ];

        if ($this->status !== GameStatus::Playing) {
            return $result;
        }

        $player = $this->getPlayer($playerId);
        if ($player === null) {
            return $result;
        }

        // Both symbols must be the same (the matching symbol)
        if ($pileSymbol !== $handSymbol) {
            return $result;
        }

        // Check if symbol exists on both pile and hand card
        if (! in_array($pileSymbol, $this->pileCard, true) || ! in_array($handSymbol, $this->handCard, true)) {
            return $result;
        }

        // Valid match! Player scores a point
        $player->score++;

        // The hand card becomes the new pile card
        $this->pileCard = $this->handCard;
        $result['newPileCard'] = $this->pileCard;

        // Draw next card from deck
        if ($this->deck !== []) {
            $this->handCard = array_pop($this->deck);
            $result['newHandCard'] = $this->handCard;
        } else {
            // No more cards - game over!
            $this->handCard = [];
            $this->status = GameStatus::Finished;
            $this->finishedAt = time();

            // Find winner (highest score)
            $this->winnerId = $this->determineWinner();

            $result['isGameOver'] = true;
            $result['winnerId'] = $this->winnerId;
        }

        $result['success'] = true;

        return $result;
    }

    /**
     * Determine the winner based on highest score.
     * In case of tie, first player to reach that score wins.
     */
    private function determineWinner(): ?string
    {
        $highestScore = -1;
        $winnerId = null;

        foreach ($this->players as $player) {
            if ($player->score > $highestScore) {
                $highestScore = $player->score;
                $winnerId = $player->id;
            }
        }

        return $winnerId;
    }

    public function getWinner(): ?GuestPlayer
    {
        if ($this->winnerId === null) {
            return null;
        }

        return $this->players[$this->winnerId] ?? null;
    }

    /**
     * @return array<string, int>
     */
    public function getScoreboard(): array
    {
        $scores = [];
        foreach ($this->players as $player) {
            $scores[$player->name] = $player->score;
        }
        arsort($scores);

        return $scores;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'hostId' => $this->hostId,
            'status' => $this->status->value,
            'players' => array_map(
                fn (GuestPlayer $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'score' => $p->score,
                ],
                $this->players
            ),
            'pileCard' => $this->pileCard,
            'handCard' => $this->handCard,
            'deck' => $this->deck,
            'rotationSeed' => $this->rotationSeed,
            'winnerId' => $this->winnerId,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $players = [];
        foreach ($data['players'] as $id => $playerData) {
            $players[$id] = GuestPlayer::fromArray($playerData);
        }

        return new self(
            code: $data['code'],
            hostId: $data['hostId'],
            status: GameStatus::from($data['status']),
            players: $players,
            pileCard: $data['pileCard'] ?? [],
            handCard: $data['handCard'] ?? [],
            deck: $data['deck'] ?? [],
            rotationSeed: $data['rotationSeed'] ?? 0,
            winnerId: $data['winnerId'] ?? null,
            startedAt: $data['startedAt'] ?? null,
            finishedAt: $data['finishedAt'] ?? null,
        );
    }

    /**
     * @return array<int, string>
     */
    private function serializeCard(Card $card): array
    {
        return array_keys($card->getSymbols());
    }
}
