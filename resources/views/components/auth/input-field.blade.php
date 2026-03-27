@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'toggleable' => false,
])

<div>
    <x-input-label :for="$id" :value="$label" class="text-sm text-slate-700 dark:text-gray-200" />

    <div class="relative mt-1">
        @if (isset($icon))
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-400">
                {{ $icon }}
            </span>
        @endif

        <input
            id="{{ $id }}"
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $value }}"
            autocomplete="{{ $autocomplete }}"
            @required($required)
            @autofocus($autofocus)
            class="relative z-10 block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-11 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500 pointer-events-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-teal-400 dark:focus:ring-teal-400"
            @if($toggleable) data-password-input @endif
        />

        @if ($toggleable)
            <button
                type="button"
                data-password-toggle
                aria-label="Toggle password visibility"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500/40 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <span data-password-icon-show>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
                <span data-password-icon-hide class="hidden">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m3 3 18 18" />
                        <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                        <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.8 21.8 0 0 1-4.22 5.35" />
                        <path d="M6.61 6.61A22.52 22.52 0 0 0 1 12s4 7 11 7a10.94 10.94 0 0 0 4.21-.83" />
                    </svg>
                </span>
            </button>
        @endif
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
