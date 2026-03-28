@props([
    'id' => null,
    'eyebrow' => null,
    'title' => '',
    'description' => null,
    'wrapperClass' => '',
    'containerClass' => '',
    'eyebrowClass' => 'text-cyan-600 dark:text-cyan-300',
    'titleClass' => 'text-gray-900 dark:text-gray-100',
    'descriptionClass' => 'text-gray-600 dark:text-gray-300',
])

<section @if ($id) id="{{ $id }}" @endif
    class="px-4 py-16 sm:px-6 sm:py-20 lg:px-8 {{ $wrapperClass }}">
    <div class="mx-auto max-w-7xl {{ $containerClass }}">
        <x-landing.reveal>
            <div class="mx-auto max-w-3xl text-center">
                @if ($eyebrow)
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $eyebrowClass }}">{{ $eyebrow }}
                    </p>
                @endif

                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl {{ $titleClass }}">
                    {{ $title }}</h2>

                @if ($description)
                    <p class="mt-4 text-base leading-relaxed sm:text-lg {{ $descriptionClass }}">{{ $description }}</p>
                @endif
            </div>
        </x-landing.reveal>

        {{ $slot }}
    </div>
</section>
