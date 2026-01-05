<div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6 lg:p-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold leading-tight">Spot It — Solo</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Drag a symbol to its match on the other card.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('multiplayer.lobby') }}"
                    wire:navigate
                    class="rounded-sm border border-[#19140035] bg-white px-4 py-2 text-sm font-medium leading-normal shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-[#62605b]"
                >
                    Multiplayer
                </a>
                <button
                    type="button"
                    wire:click="startNewGame"
                    class="rounded-sm border border-[#19140035] bg-white px-4 py-2 text-sm font-medium leading-normal shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-[#62605b]"
                >
                    New Game
                </button>
            </div>
        </div>

        @if (! $hasStarted)
            <div class="flex flex-1 items-center justify-center py-16">
                <div class="flex w-full max-w-md flex-col items-center gap-4 text-center">
                    <div class="text-lg font-semibold">Ready when you are</div>
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Click <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">New Game</span> to deal the first cards.
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center gap-4 text-sm">
                <div class="rounded-sm border border-[#19140035] bg-white px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <span class="font-medium">Pile:</span> {{ $pileCount }}
                </div>
                <div class="rounded-sm border border-[#19140035] bg-white px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <span class="font-medium">Cards left:</span> {{ $handRemaining }}
                </div>
            </div>

            <div
                x-data="{
                    shake: false,
                    matching: false,
                    dragging: null,
                    dragSource: null,
                    dropTarget: null,
                    handleDragStart(symbol, source) {
                        if ($wire.isAnimating) return;
                        this.dragging = symbol;
                        this.dragSource = source;
                    },
                    handleDragEnd() {
                        this.dragging = null;
                        this.dragSource = null;
                        this.dropTarget = null;
                    },
                    handleDrop(symbol, target) {
                        if (!this.dragging || this.matching) return;
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
                    isValidDropTarget(symbol, target) {
                        if (!this.dragging) return false;
                        if (this.dragSource === target) return false;
                        return this.dragging === symbol;
                    }
                }"
                x-on:spotit-shake.window="shake = true; setTimeout(() => shake = false, 350)"
                x-on:spotit-match.window="
                    if (matching) return;

                    matching = true;

                    setTimeout(() => {
                        $wire.completeMatch()
                            .then(() => matching = false)
                            .catch(() => matching = false);
                    }, 360);
                "
                class="grid gap-6 lg:grid-cols-2"
            >
                <section
                    class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
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
                                draggable="true"
                                x-on:dragstart="handleDragStart(@js($symbol), 'pile')"
                                x-on:dragend="handleDragEnd()"
                                x-on:dragover.prevent="dropTarget = 'pile-' + @js($symbol)"
                                x-on:dragleave="dropTarget = null"
                                x-on:drop.prevent="handleDrop(@js($symbol), 'pile')"
                                x-bind:class="{
                                    'opacity-50 scale-95': dragging === @js($symbol) && dragSource === 'pile',
                                    'ring-4 ring-sky-500 ring-offset-2 ring-offset-white dark:ring-offset-[#161615] border-transparent': isValidDropTarget(@js($symbol), 'pile'),
                                    'spotit-match spotit-match-pile': $wire.isAnimating && $wire.pendingPileSymbol === @js($symbol),
                                    'pointer-events-none opacity-70': $wire.isAnimating,
                                    'cursor-grab active:cursor-grabbing': !$wire.isAnimating
                                }"
                                @class([
                                    'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-3xl shadow-sm transition-all sm:text-4xl dark:bg-[#0a0a0a] select-none',
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

                <section
                    class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    x-bind:class="{ 'spotit-shake': shake }"
                >
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">YOUR HAND</h2>
                    </div>

                    @if ($isOver)
                        <div class="rounded-md border border-[#19140035] bg-[#FDFDFC] p-6 text-center dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            <div class="text-lg font-semibold">Game over</div>
                            @if ($this->duration)
                                <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Duration: {{ $this->duration }}</div>
                            @endif
                            <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Hit "New Game" to play again.</div>
                        </div>
                    @else
                        <div class="rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <div class="grid grid-cols-4 gap-3">
                                @foreach ($handCard as $symbol)
                                    <div
                                        wire:key="hand-{{ md5($symbol) }}"
                                        draggable="true"
                                        x-on:dragstart="handleDragStart(@js($symbol), 'hand')"
                                        x-on:dragend="handleDragEnd()"
                                        x-on:dragover.prevent="dropTarget = 'hand-' + @js($symbol)"
                                        x-on:dragleave="dropTarget = null"
                                        x-on:drop.prevent="handleDrop(@js($symbol), 'hand')"
                                        x-bind:class="{
                                            'opacity-50 scale-95': dragging === @js($symbol) && dragSource === 'hand',
                                            'ring-4 ring-emerald-500 ring-offset-2 ring-offset-white dark:ring-offset-[#161615] border-transparent': isValidDropTarget(@js($symbol), 'hand'),
                                            'spotit-match spotit-match-hand': $wire.isAnimating && $wire.pendingMatchSymbol === @js($symbol),
                                            'pointer-events-none opacity-70': $wire.isAnimating,
                                            'cursor-grab active:cursor-grabbing': !$wire.isAnimating
                                        }"
                                        @class([
                                            'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-3xl shadow-sm transition-all sm:text-4xl dark:bg-[#0a0a0a] select-none',
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
        @endif
    </div>
</div>
