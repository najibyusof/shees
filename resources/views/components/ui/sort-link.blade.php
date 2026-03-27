@props(['column', 'label', 'sort' => null, 'direction' => 'asc'])

@php
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $query = array_merge(request()->except('page'), ['sort' => $column, 'direction' => $nextDirection]);
    $url = request()->url() . '?' . http_build_query($query);
@endphp

<a href="{{ $url }}" class="inline-flex items-center gap-1.5 hover:ui-text">
    <span>{{ $label }}</span>
    @if ($isActive)
        <x-ui.icon :name="$direction === 'asc' ? 'sort-up' : 'sort-down'" class="h-3.5 w-3.5" />
    @else
        <x-ui.icon name="sort" class="h-3.5 w-3.5 opacity-60" />
    @endif
</a>
