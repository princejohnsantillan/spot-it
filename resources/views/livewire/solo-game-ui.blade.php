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
                    New game
                </button>

                <a
                    href="/"
                    class="rounded-sm border border-transparent px-4 py-2 text-sm font-medium leading-normal hover:border-[#19140035] dark:hover:border-[#3E3E3A]"
                >
                    Home
                </a>
            </div>
        </div>

        <div class="flex items-center gap-4 text-sm">
            <div class="rounded-sm border border-[#19140035] bg-white px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <span class="font-medium">Pile:</span> {{ $pileCount }}
            </div>
            <div class="rounded-sm border border-[#19140035] bg-white px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <span class="font-medium">Cards left:</span> {{ count($hand) }}
            </div>
        </div>

        <div
            x-data="{ shake: false }"
            x-on:spotit-shake.window="shake = true; setTimeout(() => shake = false, 350)"
            class="grid gap-6 lg:grid-cols-2"
        >
            <section
                class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                x-bind:class="{ 'spotit-shake': shake }"
            >
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">PILE</h2>
                    <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Selected:
                        <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $selectedPileSymbol ?? '—' }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @foreach ($pileCard as $symbol)
                        <button
                            type="button"
                            wire:key="pile-{{ $symbol }}"
                            wire:click="selectPileSymbol(@js($symbol))"
                            class="flex aspect-square items-center justify-center rounded-md border border-[#19140035] bg-[#FDFDFC] text-4xl shadow-sm transition-all hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:hover:border-[#62605b]"
                            @class([
                                'ring-4 ring-blue-500' => $selectedPileSymbol === $symbol,
                            ])
                        >
                            {{ $symbol }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section
                class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                x-bind:class="{ 'spotit-shake': shake }"
            >
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">YOUR HAND</h2>
                    <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Selected:
                        <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $selectedHandSymbol ?? '—' }}</span>
                    </div>
                </div>

                @if ($isOver)
                    <div class="rounded-md border border-[#19140035] bg-[#FDFDFC] p-6 text-center dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                        <div class="text-lg font-semibold">Game over</div>
                        <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Hit “New game” to play again.</div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($handCard as $symbol)
                            <button
                                type="button"
                                wire:key="hand-{{ $symbol }}"
                                wire:click="selectHandSymbol(@js($symbol))"
                                class="flex aspect-square items-center justify-center rounded-md border border-[#19140035] bg-[#FDFDFC] text-4xl shadow-sm transition-all hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:hover:border-[#62605b]"
                                @class([
                                    'ring-4 ring-emerald-500' => $selectedHandSymbol === $symbol,
                                ])
                            >
                                {{ $symbol }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
