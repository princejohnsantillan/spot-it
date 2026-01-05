<div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8 p-6 lg:p-8">
        <div class="flex flex-col gap-2 text-center">
            <h1 class="text-2xl font-semibold leading-tight">Spot It — Join Table</h1>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                @if ($tableExists)
                    Enter your name to join the game.
                @else
                    This table doesn't exist or has expired.
                @endif
            </p>
        </div>

        @if ($error)
            <div class="rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                {{ $error }}
            </div>
        @endif

        @if ($tableExists)
            @if ($tableStatus === 'waiting')
                <div class="flex flex-col gap-6">
                    {{-- Table Info --}}
                    <div class="flex flex-col items-center gap-2 text-center">
                        <div class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Table Code</div>
                        <div class="rounded-lg bg-[#F4F3EF] px-6 py-3 font-mono text-2xl font-bold tracking-[0.3em] dark:bg-[#161615]">
                            {{ $tableCode }}
                        </div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {{ $playerCount }} / {{ \App\Multiplayer\GameTable::MAX_PLAYERS }} players
                        </div>
                    </div>

                    {{-- Nickname Input --}}
                    <div class="flex flex-col gap-3 rounded-lg border border-[#19140035] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <label for="nickname" class="text-sm font-medium">Your Nickname</label>
                        <input
                            type="text"
                            id="nickname"
                            wire:model="nickname"
                            maxlength="20"
                            placeholder="Enter your nickname"
                            class="w-full rounded-md border border-[#19140035] bg-[#FDFDFC] px-4 py-3 text-base shadow-sm placeholder:text-[#706f6c] focus:border-[#1915014a] focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:placeholder:text-[#A1A09A] dark:focus:border-[#62605b]"
                        />
                        @error('nickname')
                            <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <button
                            type="button"
                            wire:click="joinTable"
                            wire:loading.attr="disabled"
                            class="w-full rounded-md bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-[#0a0a0a]"
                        >
                            <span wire:loading.remove wire:target="joinTable">Join Table</span>
                            <span wire:loading wire:target="joinTable">Joining...</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="rounded-md border border-amber-300 bg-amber-50 p-4 text-center text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300">
                    This game has already started. You cannot join mid-game.
                </div>
            @endif
        @else
            <div class="rounded-md border border-[#19140035] bg-[#F4F3EF] p-6 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                <div class="text-lg font-semibold">Table Not Found</div>
                <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    The table code <span class="font-mono font-semibold">{{ $tableCode }}</span> doesn't exist or has expired.
                </div>
            </div>
        @endif

        <div class="text-center">
            <a
                href="{{ route('multiplayer.lobby') }}"
                wire:navigate
                class="text-sm text-[#706f6c] underline decoration-[#706f6c]/30 underline-offset-4 transition hover:text-[#1b1b18] hover:decoration-[#1b1b18]/30 dark:text-[#A1A09A] dark:decoration-[#A1A09A]/30 dark:hover:text-[#EDEDEC] dark:hover:decoration-[#EDEDEC]/30"
            >
                Go to Multiplayer Lobby
            </a>
        </div>
    </div>
</div>
