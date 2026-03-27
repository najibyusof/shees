@props([
    'name' => 'circle',
])

@switch($name)
    @case('dashboard')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M4.5 10.5h6.75V4.5H4.5v6.0Zm8.25 9h6.75v-9h-6.75v9Zm0-10.5h6.75v-4.5h-6.75V9Zm-8.25 10.5h6.75V12H4.5v7.5Z" />
        </svg>
    @break

    @case('profile')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Zm-11.25 12a7.5 7.5 0 0 1 15 0" />
        </svg>
    @break

    @case('users')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M16.5 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-10.5 1.5a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Zm12 0a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM2.25 19.5a5.25 5.25 0 0 1 10.5 0m1.5 0a5.25 5.25 0 0 1 10.5 0" />
        </svg>
    @break

    @case('audit')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h6m-6 3h3M7.5 3.75h9A1.5 1.5 0 0 1 18 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 18.75V5.25a1.5 1.5 0 0 1 1.5-1.5Z" />
        </svg>
    @break

    @case('incident')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h16.98A1.5 1.5 0 0 0 21.38 18L12.91 3.86a1.5 1.5 0 0 0-2.62 0ZM12 9.75v4.5m0 3h.008" />
        </svg>
    @break

    @case('training')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 6.75 3 10.5l9 3.75 9-3.75-9-3.75Zm0 0v10.5m-6.75-4.5v3.75a6.75 6.75 0 0 0 13.5 0v-3.75" />
        </svg>
    @break

    @case('checklist')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.25 6.75h10.5M8.25 12h10.5M8.25 17.25h10.5M3.75 6.75h.008M3.75 12h.008M3.75 17.25h.008" />
        </svg>
    @break

    @case('inspection')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M16.5 3.75h-9A2.25 2.25 0 0 0 5.25 6v12A2.25 2.25 0 0 0 7.5 20.25h9A2.25 2.25 0 0 0 18.75 18V6A2.25 2.25 0 0 0 16.5 3.75ZM9 8.25h6m-6 3h6m-6 3h3" />
        </svg>
    @break

    @case('sort')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 6 3.75-3.75L15.75 6M12 2.25V21.75" />
        </svg>
    @break

    @case('sort-up')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 9 3.75-3.75L15.75 9M12 5.25V21.75" />
        </svg>
    @break

    @case('sort-down')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15-3.75 3.75L8.25 15M12 2.25V18.75" />
        </svg>
    @break

    @case('sun')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 3v2.25M12 18.75V21M4.5 12H2.25m19.5 0H19.5M6.343 6.343l-1.59-1.59m14.494 14.494-1.59-1.59M6.343 17.657l-1.59 1.59m14.494-14.494-1.59 1.59M15.75 12A3.75 3.75 0 1 1 8.25 12a3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
    @break

    @case('moon')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3a7.5 7.5 0 0 0 9.79 9.79Z" />
        </svg>
    @break

    @case('bell')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.25 18.75a2.25 2.25 0 1 1-4.5 0M4.5 17.25h15a1.5 1.5 0 0 0-1.5-1.5h-12a1.5 1.5 0 0 0-1.5 1.5Zm1.5-1.5V10.5a6 6 0 1 1 12 0v5.25" />
        </svg>
    @break

    @case('menu')
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    @break

    @default
        <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="8" />
        </svg>
@endswitch
