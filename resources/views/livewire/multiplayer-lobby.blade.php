<div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8 p-6 lg:p-8">
        <div class="flex flex-col gap-2 text-center">
            <h1 class="text-2xl font-semibold leading-tight">Spot It — Multiplayer</h1>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Create a new room or join an existing one.
            </p>
        </div>

        @if ($error)
            <div class="rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                {{ $error }}
            </div>
        @endif

        <div class="flex flex-col gap-6">
            {{-- Nickname Input --}}
            <div class="flex flex-col gap-2">
                <label for="nickname" class="text-sm font-medium">Your Nickname</label>
                <input
                    type="text"
                    id="nickname"
                    wire:model="nickname"
                    maxlength="20"
                    placeholder="Enter your nickname"
                    class="w-full rounded-md border border-[#19140035] bg-white px-4 py-3 text-base shadow-sm placeholder:text-[#706f6c] focus:border-[#1915014a] focus:outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-[#3E3E3A] dark:bg-[#161615] dark:placeholder:text-[#A1A09A] dark:focus:border-[#62605b]"
                />
                @error('nickname')
                    <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            {{-- Create Room --}}
            <div class="flex flex-col gap-3 rounded-lg border border-[#19140035] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="font-semibold">Create a New Room</h2>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Start a new game and invite friends with the room code.
                </p>
                <button
                    type="button"
                    wire:click="createRoom"
                    wire:loading.attr="disabled"
                    class="w-full rounded-md bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-[#0a0a0a]"
                >
                    <span wire:loading.remove wire:target="createRoom">Create Room</span>
                    <span wire:loading wire:target="createRoom">Creating...</span>
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div class="h-px flex-1 bg-[#19140035] dark:bg-[#3E3E3A]"></div>
                <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">or</span>
                <div class="h-px flex-1 bg-[#19140035] dark:bg-[#3E3E3A]"></div>
            </div>

            {{-- Join Room --}}
            <div class="flex flex-col gap-3 rounded-lg border border-[#19140035] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="font-semibold">Join a Room</h2>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Enter a 6-letter room code to join an existing game.
                </p>
                <div class="flex flex-col gap-3">
                    <input
                        type="text"
                        wire:model="roomCode"
                        maxlength="6"
                        placeholder="ABCDEF"
                        class="w-full rounded-md border border-[#19140035] bg-[#FDFDFC] px-4 py-3 text-center text-lg font-mono uppercase tracking-widest shadow-sm placeholder:text-[#706f6c] focus:border-[#1915014a] focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:placeholder:text-[#A1A09A] dark:focus:border-[#62605b]"
                    />
                    @error('roomCode')
                        <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                    <button
                        type="button"
                        wire:click="joinRoom"
                        wire:loading.attr="disabled"
                        class="w-full rounded-md bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-[#0a0a0a]"
                    >
                        <span wire:loading.remove wire:target="joinRoom">Join Room</span>
                        <span wire:loading wire:target="joinRoom">Joining...</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a
                href="{{ route('solo') }}"
                wire:navigate
                class="text-sm text-[#706f6c] underline decoration-[#706f6c]/30 underline-offset-4 transition hover:text-[#1b1b18] hover:decoration-[#1b1b18]/30 dark:text-[#A1A09A] dark:decoration-[#A1A09A]/30 dark:hover:text-[#EDEDEC] dark:hover:decoration-[#EDEDEC]/30"
            >
                Play Solo Instead
            </a>
        </div>
    </div>
</div>
