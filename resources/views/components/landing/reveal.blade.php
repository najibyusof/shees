@props([
    'delay' => 0,
    'as' => 'div',
])

@php
    $delayClass = '[transition-delay:' . ((int) $delay) . 'ms]';
@endphp

<{{ $as }}
    {{ $attributes->merge([
        'data-reveal' => true,
        'class' => 'opacity-0 translate-y-3 transition-all duration-700 will-change-transform ' . $delayClass,
    ]) }}
>
    {{ $slot }}
</{{ $as }}>
