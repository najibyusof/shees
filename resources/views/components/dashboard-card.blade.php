@props([
    'icon' => 'dashboard',
    'value' => 0,
    'label' => '',
    'module' => null,
    'trend' => null,
    'loading' => false,
])

@php
    $trendDirection = data_get($trend, 'direction', 'neutral');
    $trendLabel = data_get($trend, 'label');
    $trendPalette = match ($trendDirection) {
        'up' => 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/40',
        'down' => 'text-rose-700 bg-rose-100 dark:text-rose-300 dark:bg-rose-900/40',
        default => 'text-slate-700 bg-slate-100 dark:text-slate-300 dark:bg-slate-700/50',
    };

    $trendArrow = match ($trendDirection) {
        'up' => '↑',
        'down' => '↓',
        default => '→',
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'min-h-[126px] rounded-xl border ui-border bg-white p-3 shadow-sm transition hover:shadow-md dark:bg-gray-900']) }}>
    @if ($loading)
        <div class="flex items-start justify-between gap-4">
            <div>
                <x-ui.skeleton :lines="1" height="h-3" class="w-24" />
                <x-ui.skeleton :lines="1" height="h-6" class="mt-3 w-20" />
            </div>
            <x-ui.skeleton :lines="1" height="h-8" class="w-8" />
        </div>
        <x-ui.skeleton :lines="1" height="h-5" class="mt-4 w-28" />
    @else
        <div class="flex items-start justify-between gap-4">
            <div>
                @if ($module)
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">
                        {{ $module }}</p>
                @endif
                <p class="mt-1 text-sm font-medium ui-text">{{ $label }}</p>
            </div>
            <span
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        </div>

        <div class="mt-2 flex items-end justify-between gap-3">
            <p class="text-2xl font-bold ui-text">{{ is_numeric($value) ? number_format((float) $value) : $value }}</p>
            @if ($trendLabel)
                <span
                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $trendPalette }}">
                    <span aria-hidden="true">{{ $trendArrow }}</span>
                    <span>{{ $trendLabel }}</span>
                </span>
            @endif
        </div>
    @endif
</div>
