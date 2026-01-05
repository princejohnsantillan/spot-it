<div
    class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    x-data="{
        shake: false,
        scoringPlayerId: null,
        dragging: null,
        dragSource: null,
        touchDragEl: null,
        touchOffset: { x: 0, y: 0 },

        // Mouse drag events
        handleDragStart(symbol, source) {
            this.dragging = symbol;
            this.dragSource = source;
        },
        handleDragEnd() {
            this.dragging = null;
            this.dragSource = null;
        },
        handleDrop(symbol, target) {
            if (!this.dragging) return;
            if (this.dragSource === target) return;

            let pileSymbol, handSymbol;
            if (this.dragSource === 'pile') {
                pileSymbol = this.dragging;
                handSymbol = symbol;
            } else {
                pileSymbol = symbol;
                handSymbol = this.dragging;
            }

            $wire.attemptMatch(pileSymbol, handSymbol);
            this.handleDragEnd();
        },

        // Touch events
        handleTouchStart(e, symbol, source) {
            e.preventDefault();

            const touch = e.touches[0];
            this.dragging = symbol;
            this.dragSource = source;

            // Create floating drag element immediately
            const target = e.target.closest('[data-symbol]');
            const rect = target.getBoundingClientRect();

            // Store offset for transform-based positioning
            this.touchOffset = {
                x: rect.width / 2,
                y: rect.height / 2
            };

            this.touchDragEl = target.cloneNode(true);
            this.touchDragEl.classList.add('spotit-touch-drag');
            this.touchDragEl.style.width = rect.width + 'px';
            this.touchDragEl.style.height = rect.height + 'px';
            // Use transform for GPU-accelerated positioning
            this.touchDragEl.style.transform = `translate3d(${touch.clientX - this.touchOffset.x}px, ${touch.clientY - this.touchOffset.y}px, 0) scale(1.05)`;
            document.body.appendChild(this.touchDragEl);

            target.classList.add('opacity-50', 'scale-95');
        },
        handleTouchMove(e) {
            if (!this.dragging || !this.touchDragEl) return;

            e.preventDefault();
            const touch = e.touches[0];

            // Use transform for smooth GPU-accelerated movement
            this.touchDragEl.style.transform = `translate3d(${touch.clientX - this.touchOffset.x}px, ${touch.clientY - this.touchOffset.y}px, 0) scale(1.05)`;
        },
        handleTouchEnd(e) {
            if (!this.dragging) return;

            // Reset original element
            const original = document.querySelector('[data-symbol].opacity-50');
            if (original) original.classList.remove('opacity-50', 'scale-95');

            // Remove floating element
            if (this.touchDragEl) {
                this.touchDragEl.remove();
                this.touchDragEl = null;
            }

            // Find element under last touch point
            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);

            if (elementBelow) {
                const dropZone = elementBelow.closest('[data-symbol]');
                if (dropZone && dropZone.dataset.source !== this.dragSource) {
                    const targetSymbol = dropZone.dataset.symbol;
                    let pileSymbol, handSymbol;

                    if (this.dragSource === 'pile') {
                        pileSymbol = this.dragging;
                        handSymbol = targetSymbol;
                    } else {
                        pileSymbol = targetSymbol;
                        handSymbol = this.dragging;
                    }

                    $wire.attemptMatch(pileSymbol, handSymbol);
                }
            }

            this.handleDragEnd();
        }
    }"
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
                        {{ $roomCode }}
                    </span>
                </div>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    @if ($status === 'waiting')
                        Waiting for players to join...
                    @elseif ($status === 'playing')
                        Drag the matching symbol to its pair on the other card!
                    @else
                        Game Over!
                    @endif
                </p>
            </div>

            <button
                type="button"
                wire:click="leaveRoom"
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

        {{-- Waiting State --}}
        @if ($status === 'waiting')
            <div class="flex flex-1 flex-col items-center justify-center gap-6 py-16">
                <div class="flex flex-col items-center gap-2 text-center">
                    <div class="text-lg font-semibold">Room Code</div>
                    <div class="rounded-lg bg-[#F4F3EF] px-6 py-4 font-mono text-3xl font-bold tracking-[0.3em] dark:bg-[#161615]">
                        {{ $roomCode }}
                    </div>
                    <div class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Share this code with friends to join
                    </div>
                </div>

                <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {{ count($players) }} / {{ \App\Multiplayer\GameRoom::MAX_PLAYERS }} players
                    @if (count($players) < \App\Multiplayer\GameRoom::MIN_PLAYERS)
                        <span class="text-amber-600 dark:text-amber-400">
                            (need at least {{ \App\Multiplayer\GameRoom::MIN_PLAYERS }})
                        </span>
                    @endif
                </div>

                @if ($isHost)
                    <button
                        type="button"
                        wire:click="startGame"
                        @disabled(count($players) < \App\Multiplayer\GameRoom::MIN_PLAYERS)
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
                <section
                    class="relative rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    x-bind:class="{ 'spotit-shake': shake }"
                >
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">PILE</h2>
                    </div>

                    <div class="rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]">
                        <div class="grid grid-cols-4 gap-3">
                            @foreach ($pileCard as $symbol)
                                <div
                                    wire:key="pile-{{ md5($symbol) }}"
                                    data-symbol="{{ $symbol }}"
                                    data-source="pile"
                                    draggable="{{ $handCard !== [] ? 'true' : 'false' }}"
                                    x-on:dragstart="handleDragStart(@js($symbol), 'pile')"
                                    x-on:dragend="handleDragEnd()"
                                    x-on:dragover.prevent
                                    x-on:drop.prevent="handleDrop(@js($symbol), 'pile')"
                                    @if($handCard !== [])
                                    x-on:touchstart="handleTouchStart($event, @js($symbol), 'pile')"
                                    x-on:touchmove="handleTouchMove($event)"
                                    x-on:touchend="handleTouchEnd($event)"
                                    @endif
                                    x-bind:class="{
                                        'opacity-50 scale-95': dragging === @js($symbol) && dragSource === 'pile',
                                        'cursor-grab active:cursor-grabbing': {{ $handCard !== [] ? 'true' : 'false' }}
                                    }"
                                    @class([
                                        'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-3xl shadow-sm transition-all sm:text-4xl dark:bg-[#0a0a0a] select-none touch-none',
                                        'pointer-events-none opacity-70' => $handCard === [],
                                        'border-[#19140035] dark:border-[#3E3E3A]',
                                    ])
                                >
                                    <span class="inline-block pointer-events-none" style="transform: rotate({{ $pileRotations[$symbol] ?? 0 }}deg)">
                                        {{ $symbol }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Hand Card (Shared) --}}
                <section
                    class="relative rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    x-bind:class="{ 'spotit-shake': shake }"
                >
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">HAND</h2>
                    </div>

                    @if ($handCard === [])
                        <div class="rounded-md border border-emerald-300 bg-emerald-50 p-6 text-center dark:border-emerald-800 dark:bg-emerald-950">
                            <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">No more cards!</div>
                            <div class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">Game ending...</div>
                        </div>
                    @else
                        <div class="rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <div class="grid grid-cols-4 gap-3">
                                @foreach ($handCard as $symbol)
                                    <div
                                        wire:key="hand-{{ md5($symbol) }}"
                                        data-symbol="{{ $symbol }}"
                                        data-source="hand"
                                        draggable="true"
                                        x-on:dragstart="handleDragStart(@js($symbol), 'hand')"
                                        x-on:dragend="handleDragEnd()"
                                        x-on:dragover.prevent
                                        x-on:drop.prevent="handleDrop(@js($symbol), 'hand')"
                                        x-on:touchstart="handleTouchStart($event, @js($symbol), 'hand')"
                                        x-on:touchmove="handleTouchMove($event)"
                                        x-on:touchend="handleTouchEnd($event)"
                                        x-bind:class="{
                                            'opacity-50 scale-95': dragging === @js($symbol) && dragSource === 'hand',
                                            'cursor-grab active:cursor-grabbing': true
                                        }"
                                        @class([
                                            'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-3xl shadow-sm transition-all sm:text-4xl dark:bg-[#0a0a0a] select-none touch-none',
                                            'border-[#19140035] dark:border-[#3E3E3A]',
                                        ])
                                    >
                                        <span class="inline-block pointer-events-none" style="transform: rotate({{ $handRotations[$symbol] ?? 0 }}deg)">
                                            {{ $symbol }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
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
                    <div class="text-4xl">🎉</div>
                    <div class="text-2xl font-semibold">
                        @if ($winnerId === $playerId)
                            You Win!
                        @else
                            {{ $winnerName }} Wins!
                        @endif
                    </div>
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
