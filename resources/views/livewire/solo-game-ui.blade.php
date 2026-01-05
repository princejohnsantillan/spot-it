<div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6 lg:p-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold leading-tight">Spot It — Solo</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Click one symbol on the pile card, then one on your hand card.
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
                x-data="{ shake: false, matching: false }"
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
                <x-spot-it-card
                    label="PILE"
                    :symbols="$pileCard"
                    :rotations="$pileRotations"
                    :selected-symbol="$selectedPileSymbol"
                    :matching-symbol="$pendingMatchSymbol"
                    :is-animating="$isAnimating"
                    :disabled="$isAnimating"
                    card-type="pile"
                    shake="shake"
                    wire-click-method="selectPileSymbol"
                />

                @if ($isOver)
                    <section
                        class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    >
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">YOUR HAND</h2>
                        </div>

                        <div class="rounded-md border border-[#19140035] bg-[#FDFDFC] p-6 text-center dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            <div class="text-lg font-semibold">Game over</div>
                            @if ($this->duration)
                                <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Duration: {{ $this->duration }}</div>
                            @endif
                            <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Hit "New Game" to play again.</div>
                        </div>
                    </section>
                @else
                    <x-spot-it-card
                        label="YOUR HAND"
                        :symbols="$handCard"
                        :rotations="$handRotations"
                        :selected-symbol="$selectedHandSymbol"
                        :matching-symbol="$pendingMatchSymbol"
                        :is-animating="$isAnimating"
                        :disabled="$isAnimating"
                        card-type="hand"
                        shake="shake"
                        wire-click-method="selectHandSymbol"
                    />
                @endif
            </div>
        @endif
    </div>
</div>
