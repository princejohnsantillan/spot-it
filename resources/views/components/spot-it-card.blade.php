@props([
    'label',
    'symbols' => [],
    'rotations' => [],
    'selectedSymbol' => null,
    'matchingSymbol' => null,
    'isAnimating' => false,
    'disabled' => false,
    'ringColor' => 'sky',
    'shake' => false,
    'wireClickMethod',
])

@php
    $ringColorClasses = match ($ringColor) {
        'emerald' => 'ring-emerald-500',
        default => 'ring-sky-500',
    };
@endphp

<section
    class="rounded-lg border border-[#19140035] bg-white p-5 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
    x-bind:class="{ 'spotit-shake': {{ $shake }} }"
>
    <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-sm font-semibold tracking-wide text-[#706f6c] dark:text-[#A1A09A]">{{ $label }}</h2>
    </div>

    <div class="rounded-md bg-[#F4F3EF] p-4 shadow-sm dark:bg-[#0f0f0f]">
        <div class="grid grid-cols-4 gap-3">
            @foreach ($symbols as $symbol)
                @php
                    $isSelected = $selectedSymbol === $symbol;
                    $isMatchingAndAnimating = $isAnimating && $matchingSymbol === $symbol;
                    $matchClass = $ringColor === 'emerald' ? 'spotit-match spotit-match-hand' : 'spotit-match spotit-match-pile';
                @endphp
                <button
                    type="button"
                    wire:key="{{ $ringColor }}-{{ md5($symbol) }}"
                    wire:click="{{ $wireClickMethod }}(@js($symbol))"
                    @disabled($disabled)
                    @class([
                        'flex aspect-square items-center justify-center rounded-md border bg-[#FDFDFC] text-3xl shadow-sm transition-all hover:border-[#1915014a] sm:text-4xl dark:bg-[#0a0a0a] dark:hover:border-[#62605b]',
                        'pointer-events-none opacity-70' => $disabled,
                        'border-[#19140035] dark:border-[#3E3E3A]' => ! $isMatchingAndAnimating && ! $isSelected,
                        'border-transparent ring-4 ring-offset-2 ring-offset-white dark:ring-offset-[#161615] ' . $ringColorClasses => ! $isMatchingAndAnimating && $isSelected,
                        $matchClass => $isMatchingAndAnimating,
                    ])
                >
                    <span class="inline-block" style="transform: rotate({{ $rotations[$symbol] ?? 0 }}deg)">
                        {{ $symbol }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</section>
