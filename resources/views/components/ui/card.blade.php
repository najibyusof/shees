@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-5',
])

<div
    {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white ui-border ui-surface shadow-[0_1px_2px_rgba(16,24,40,0.04)] dark:border-gray-700 dark:bg-gray-800']) }}>
    @if ($title || $subtitle)
        <div class="border-b ui-border px-5 py-4">
            @if ($title)
                <h3 class="text-base font-semibold ui-text">{{ $title }}</h3>
            @endif

            @if ($subtitle)
                <p class="mt-1 text-sm ui-text-muted">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t ui-border px-5 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
