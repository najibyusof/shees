@props([
    'title' => '',
    'description' => '',
])

<article {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/90 p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800/90 dark:hover:border-teal-600']) }}>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-teal-50/0 via-teal-100/10 to-cyan-100/40 opacity-0 transition duration-300 group-hover:opacity-100 dark:from-teal-900/0 dark:via-teal-700/10 dark:to-cyan-700/20"></div>

    <div class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 text-white shadow-md transition duration-300 group-hover:scale-105 group-hover:-rotate-3">
        {{ $icon ?? '' }}
    </div>

    <h3 class="relative mt-5 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $title }}</h3>
    <p class="relative mt-2 text-sm leading-relaxed text-slate-600 dark:text-gray-300">{{ $description }}</p>
</article>
