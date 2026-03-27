@props(['paginator'])

@if ($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'ui-text-muted']) }}>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
