@props(['title', 'subtitle' => null])

<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight ui-text">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm ui-text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2 sm:justify-end">
            {{ $actions }}
        </div>
    @endisset
</div>
