@props([
    'title' => null,
    'description' => null,
    'triggerLabel' => null,
    'triggerVariant' => 'secondary',
    'maxWidth' => 'max-w-lg',
])

<div x-data="{ open: false }" class="inline-block">
    @isset($trigger)
        <div @click="open = true">
            {{ $trigger }}
        </div>
    @else
        @if ($triggerLabel)
            <x-ui.button :variant="$triggerVariant" size="md" @click="open = true">
                {{ $triggerLabel }}
            </x-ui.button>
        @endif
    @endisset

    <div x-cloak x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>

        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-2 opacity-0"
            class="relative z-10 w-full {{ $maxWidth }} rounded-xl border ui-border ui-surface p-5 shadow-xl">
            @if ($title)
                <h3 class="text-lg font-semibold ui-text">{{ $title }}</h3>
            @endif

            @if ($description)
                <p class="mt-1 text-sm ui-text-muted">{{ $description }}</p>
            @endif

            <div class="mt-4">
                {{ $slot }}
            </div>

            @isset($actions)
                <div class="mt-5 flex justify-end gap-2">
                    {{ $actions }}
                </div>
            @else
                <div class="mt-5 flex justify-end">
                    <x-ui.button variant="secondary" @click="open = false">Close</x-ui.button>
                </div>
            @endisset
        </div>
    </div>
</div>
