@props([
    'title' => '',
    'description' => '',
])

<article
    {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-cyan-300 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800 dark:hover:border-cyan-600']) }}>
    <div
        class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-50/0 via-cyan-100/40 to-emerald-100/60 opacity-0 transition duration-300 group-hover:opacity-100 dark:from-cyan-900/0 dark:via-cyan-700/10 dark:to-emerald-700/20">
    </div>

    <div
        class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-600 to-emerald-500 text-white shadow-md transition duration-300 group-hover:scale-105 group-hover:-rotate-3">
        {{ $icon ?? '' }}
    </div>

    <h3 class="relative mt-5 font-display text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}
    </h3>
    <p class="relative mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $description }}</p>
</article>
