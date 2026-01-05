@props([
    'label',
    'symbols' => [],
    'rotations' => [],
    'selectedSymbol' => null,
    'matchingSymbol' => null,
    'isAnimating' => false,
    'disabled' => false,
    'cardType' => 'pile',
    'shake' => false,
    'wireClickMethod',
])

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
                    $matchClass = $cardType === 'hand' ? 'spotit-match spotit-match-hand' : 'spotit-match spotit-match-pile';
                @endphp
                <button
                    type="button"
                    wire:key="{{ $cardType }}-{{ md5($symbol) }}"
                    wire:click="{{ $wireClickMethod }}(@js($symbol))"
                    @disabled($disabled)
                    @class([
                        'flex aspect-square items-center justify-center rounded-md border border-[#19140035] text-3xl shadow-sm outline-none sm:text-4xl dark:border-[#3E3E3A]',
                        'pointer-events-none opacity-70' => $disabled,
                        'bg-[#FDFDFC] dark:bg-[#0a0a0a]' => ! $isSelected,
                        'bg-[#e5e5e0] dark:bg-[#1f1f1e]' => $isSelected && ! $isMatchingAndAnimating,
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
