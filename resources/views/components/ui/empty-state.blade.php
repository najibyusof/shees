@props([
    'title' => 'No data available',
    'description' => 'There is nothing to display at the moment.',
])

<div
    {{ $attributes->merge(['class' => 'rounded-xl border border-dashed ui-border ui-surface-soft px-6 py-10 text-center']) }}>
    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full ui-surface">
        <x-ui.icon name="circle" class="h-5 w-5 ui-text-muted" />
    </div>
    <h3 class="mt-3 text-base font-semibold ui-text">{{ $title }}</h3>
    <p class="mx-auto mt-1 max-w-md text-sm ui-text-muted">{{ $description }}</p>

    @isset($actions)
        <div class="mt-4 flex justify-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
