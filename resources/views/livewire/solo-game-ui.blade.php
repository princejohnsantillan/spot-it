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
                    <span class="font-medium">Cards left:</span> {{ count($hand) }}
                </div>
            </div>

            <div
                x-data="{ shake: false, sliding: false }"
                x-on:spotit-shake.window="shake = true; setTimeout(() => shake = false, 350)"
                x-on:spotit-match.window="
                    if (sliding) return;

                    const hand = $refs.handFace;
                    const pile = $refs.pileFace;

                    if (!hand || !pile) return;

                    sliding = true;
                    $nextTick(() => {
                        const handRect = hand.getBoundingClientRect();
                        const pileRect = pile.getBoundingClientRect();

                        const clone = hand.cloneNode(true);
                        clone.style.position = 'fixed';
                        clone.style.left = `${handRect.left}px`;
                        clone.style.top = `${handRect.top}px`;
                        clone.style.width = `${handRect.width}px`;
                        clone.style.height = `${handRect.height}px`;
                        clone.style.margin = '0';
                        clone.style.zIndex = '50';
                        clone.style.pointerEvents = 'none';
                        document.body.appendChild(clone);

                        hand.style.visibility = 'hidden';

                        const deltaX = pileRect.left - handRect.left;
                        const deltaY = pileRect.top - handRect.top;

                        const animation = clone.animate(
                            [
                                { transform: 'translate(0px, 0px) scale(1)', opacity: 1 },
                                { transform: `translate(${deltaX}px, ${deltaY}px) scale(0.98)`, opacity: 1 },
                            ],
                            { duration: 380, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' }
                        );

                        animation.finished
                            .then(() => $wire.completeMatch())
                            .finally(() => {
                                clone.remove();
                                hand.style.visibility = '';
                                sliding = false;
                            });
                    });
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

                    <div class="rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]" x-ref="pileFace">
                        <div class="grid grid-cols-2 gap-3">
                        @foreach ($pileCard as $symbol)
                            <button
                                type="button"
                                wire:key="pile-{{ $symbol }}"
                                wire:click="selectPileSymbol(@js($symbol))"
                                @disabled($isAnimating)
                                @class([
                                    'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-4xl shadow-sm transition-all hover:border-[#1915014a] dark:bg-[#0a0a0a] dark:hover:border-[#62605b]',
                                    'pointer-events-none opacity-70' => $isAnimating,
                                    'border-[#19140035] dark:border-[#3E3E3A]' => $selectedPileSymbol !== $symbol,
                                    'border-transparent ring-4 ring-sky-500 ring-offset-2 ring-offset-white dark:ring-offset-[#161615]' => $selectedPileSymbol === $symbol,
                                ])
                            >
                                {{ $symbol }}
                            </button>
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
                            <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Hit “New Game” to play again.</div>
                        </div>
                    @else
                        <div class="relative">
                            @if (count($hand) > 1)
                                <div
                                    class="absolute inset-0 translate-x-2 translate-y-2 rounded-md bg-[#EDEBE3] p-4 shadow-sm dark:bg-[#0b0b0b]"
                                    aria-hidden="true"
                                ></div>
                            @endif

                            <div class="relative rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]" x-ref="handFace">
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach ($handCard as $symbol)
                                        <button
                                            type="button"
                                            wire:key="hand-{{ $symbol }}"
                                            wire:click="selectHandSymbol(@js($symbol))"
                                            @disabled($isAnimating)
                                            @class([
                                                'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-4xl shadow-sm transition-all hover:border-[#1915014a] dark:bg-[#0a0a0a] dark:hover:border-[#62605b]',
                                                'pointer-events-none opacity-70' => $isAnimating,
                                                'border-[#19140035] dark:border-[#3E3E3A]' => $selectedHandSymbol !== $symbol,
                                                'border-transparent ring-4 ring-emerald-500 ring-offset-2 ring-offset-white dark:ring-offset-[#161615]' => $selectedHandSymbol === $symbol,
                                            ])
                                        >
                                            {{ $symbol }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </div>
</div>
