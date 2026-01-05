<div
    class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    x-data="{
        shake: false,
        scoringPlayerId: null,
        copied: false,
        countdownValue: @entangle('countdown'),
    }"
    x-init="
        $watch('countdownValue', (value) => {
            if (value === 5) {
                const interval = setInterval(() => {
                    if (countdownValue > 0) {
                        countdownValue--;
                        if (countdownValue === 0) {
                            clearInterval(interval);
                            @if ($isHost)
                                $wire.startGameAfterCountdown();
                            @endif
                        }
                    }
                }, 1000);
            }
        });
    "
    x-on:spotit-shake.window="shake = true; setTimeout(() => shake = false, 350)"
    x-on:spotit-score-pulse.window="
        scoringPlayerId = $event.detail.playerId;
        setTimeout(() => scoringPlayerId = null, 600);
    "
>
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6 lg:p-8">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold leading-tight">Spot It — Multiplayer</h1>
                    <span class="rounded bg-[#F4F3EF] px-2 py-1 font-mono text-sm font-medium tracking-wider dark:bg-[#161615]">
                        {{ $tableCode }}
                    </span>
                </div>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    @if ($status === 'waiting')
                        Waiting for players to join...
                    @elseif ($status === 'countdown')
                        Get ready! Game starting soon...
                    @elseif ($status === 'playing')
                        Click the matching symbol on BOTH cards to score!
                    @else
                        Game Over!
                    @endif
                </p>
            </div>

            <button
                type="button"
                wire:click="leaveTable"
                class="rounded-sm border border-[#19140035] bg-white px-4 py-2 text-sm font-medium leading-normal shadow-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-[#62605b]"
            >
                Leave
            </button>
        </div>

        {{-- Players & Scores --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($players as $player)
                <div
                    x-bind:class="{
                        'spotit-score-pulse': scoringPlayerId === '{{ $player['id'] }}'
                    }"
                    @class([
                        'flex items-center gap-2.5 rounded-full px-4 py-2 text-sm transition-all',
                        'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300' => $player['id'] === $playerId,
                        'bg-[#F4F3EF] text-[#706f6c] dark:bg-[#161615] dark:text-[#A1A09A]' => $player['id'] !== $playerId,
                    ])
                >
                    <span class="font-medium">{{ $player['name'] }}</span>
                    @if ($status === 'playing' || $status === 'finished')
                        <span
                            x-bind:class="{ 'spotit-score-pulse': scoringPlayerId === '{{ $player['id'] }}' }"
                            class="rounded-md bg-white/60 px-2.5 py-1 text-sm font-bold tabular-nums dark:bg-black/30"
                        >
                            {{ $player['score'] }} pts
                        </span>
                    @endif
                    @if ($player['id'] === $hostId && $status === 'waiting')
                        <span class="text-xs opacity-60">(host)</span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Countdown State --}}
        @if ($status === 'countdown')
            <div class="flex flex-1 flex-col items-center justify-center gap-6 py-16">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="text-2xl font-semibold">Get Ready!</div>
                    <div
                        x-show="countdownValue > 0"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        class="flex h-48 w-48 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-sky-600 text-9xl font-bold text-white shadow-2xl"
                    >
                        <span x-text="countdownValue"></span>
                    </div>
                    <div class="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                        The game will start automatically...
                    </div>
                </div>
            </div>

        {{-- Waiting State --}}
        @elseif ($status === 'waiting')
            <div class="flex flex-1 flex-col items-center justify-center gap-6 py-16">
                <div class="flex flex-col items-center gap-2 text-center">
                    <div class="text-lg font-semibold">Table Code</div>
                    <div class="rounded-lg bg-[#F4F3EF] px-6 py-4 font-mono text-3xl font-bold tracking-[0.3em] dark:bg-[#161615]">
                        {{ $tableCode }}
                    </div>
                </div>

                {{-- Copy Link Button --}}
                <div class="flex flex-col items-center gap-2">
                    <button
                        type="button"
                        x-on:click="
                            navigator.clipboard.writeText('{{ route('multiplayer.join', ['code' => $tableCode]) }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        class="flex items-center gap-2 rounded-md border border-[#19140035] bg-white px-4 py-2 text-sm font-medium shadow-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-[#62605b]"
                    >
                        <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="copied ? 'Link Copied!' : 'Copy Invite Link'"></span>
                    </button>
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Share this link with friends to join
                    </div>
                </div>

                {{-- Players at Table --}}
                <div class="flex flex-col items-center gap-3">
                    <div class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Players at table</div>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach ($players as $player)
                            <div @class([
                                'rounded-full px-3 py-1.5 text-sm font-medium',
                                'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300' => $player['id'] === $playerId,
                                'bg-[#F4F3EF] text-[#706f6c] dark:bg-[#161615] dark:text-[#A1A09A]' => $player['id'] !== $playerId,
                            ])>
                                {{ $player['name'] }}
                                @if ($player['id'] === $hostId)
                                    <span class="text-xs opacity-60">(host)</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {{ count($players) }} / {{ \App\Multiplayer\GameTable::MAX_PLAYERS }} players
                    @if (count($players) < \App\Multiplayer\GameTable::MIN_PLAYERS)
                        <span class="text-amber-600 dark:text-amber-400">
                            (need at least {{ \App\Multiplayer\GameTable::MIN_PLAYERS }})
                        </span>
                    @endif
                </div>

                @if ($isHost)
                    <button
                        type="button"
                        wire:click="startGame"
                        @disabled(count($players) < \App\Multiplayer\GameTable::MIN_PLAYERS)
                        class="rounded-md bg-emerald-600 px-6 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-[#0a0a0a]"
                    >
                        Start Game
                    </button>
                @else
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Waiting for host to start...
                    </div>
                @endif
            </div>

        {{-- Playing State --}}
        @elseif ($status === 'playing')
            {{-- Game stats --}}
            <div class="flex items-center gap-4 text-sm">
                <div class="rounded-sm border border-[#19140035] bg-white px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <span class="font-medium">Cards left:</span> {{ $cardsRemaining }}
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Pile Card --}}
                <x-spot-it-card
                    label="PILE"
                    :symbols="$pileCard"
                    :rotations="$pileRotations"
                    :selected-symbol="$selectedPileSymbol"
                    :disabled="$handCard === []"
                    card-type="pile"
                    shake="shake"
                    wire-click-method="selectPileSymbol"
                />

                {{-- Hand Card (Shared) --}}
                @if ($handCard === [])
                    <section
                        class="relative rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    >
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">HAND</h2>
                        </div>

                        <div class="rounded-md border border-emerald-300 bg-emerald-50 p-6 text-center dark:border-emerald-800 dark:bg-emerald-950">
                            <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">No more cards!</div>
                            <div class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">Game ending...</div>
                        </div>
                    </section>
                @else
                    <x-spot-it-card
                        label="HAND"
                        :symbols="$handCard"
                        :rotations="$handRotations"
                        :selected-symbol="$selectedHandSymbol"
                        card-type="hand"
                        shake="shake"
                        wire-click-method="selectHandSymbol"
                    />
                @endif
            </div>

            {{-- Match History Log --}}
            @if (count($matchHistory) > 0)
                <section class="rounded-lg border border-[#19140035] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">MATCH HISTORY</h2>
                    <div class="flex flex-col gap-1.5">
                        @foreach ($matchHistory as $match)
                            <div @class([
                                'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm',
                                'bg-sky-50 dark:bg-sky-900/20' => $match['isMe'],
                                'bg-[#F4F3EF] dark:bg-[#0f0f0f]' => !$match['isMe'],
                            ])>
                                <span class="text-xl">{{ $match['symbol'] }}</span>
                                <span @class([
                                    'font-medium',
                                    'text-sky-700 dark:text-sky-300' => $match['isMe'],
                                    'text-[#706f6c] dark:text-[#A1A09A]' => !$match['isMe'],
                                ])>
                                    {{ $match['isMe'] ? 'You' : $match['playerName'] }}
                                </span>
                                <span class="text-[#706f6c] dark:text-[#A1A09A]">matched</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        {{-- Finished State --}}
        @else
            <div class="flex flex-1 flex-col items-center justify-center gap-6 py-16">
                <div class="flex flex-col items-center gap-2 text-center">
                    @if ($winnerId === $playerId)
                        <div class="text-4xl">🎉</div>
                        <div class="text-2xl font-semibold">You Win!</div>
                    @else
                        <div class="text-4xl">😢</div>
                        <div class="text-2xl font-semibold">You lost...</div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $winnerName }} wins!</div>
                    @endif
                </div>

                {{-- Scoreboard --}}
                <div class="w-full max-w-sm rounded-lg border border-[#19140035] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h3 class="mb-4 text-center text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">FINAL SCORES</h3>
                    <div class="flex flex-col gap-2">
                        @php $rank = 1; @endphp
                        @foreach ($scoreboard as $name => $score)
                            <div @class([
                                'flex items-center justify-between rounded-md px-3 py-2',
                                'bg-amber-100 dark:bg-amber-900/30' => $rank === 1,
                                'bg-[#F4F3EF] dark:bg-[#0f0f0f]' => $rank !== 1,
                            ])>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">#{{ $rank }}</span>
                                    <span class="font-medium">{{ $name }}</span>
                                    @if ($rank === 1)
                                        <span>👑</span>
                                    @endif
                                </div>
                                <span class="font-mono text-sm font-semibold">{{ $score }} pts</span>
                            </div>
                            @php $rank++; @endphp
                        @endforeach
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="playAgain"
                    class="rounded-md bg-sky-600 px-6 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0a]"
                >
                    Play Again
                </button>
            </div>
        @endif
    </div>
</div>
