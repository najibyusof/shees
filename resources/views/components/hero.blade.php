@props([
    'eyebrow' => '',
    'title' => '',
    'description' => '',
    'primaryText' => 'Login',
    'primaryLink' => '#',
    'secondaryText' => 'Explore Features',
    'secondaryLink' => '#',
    'stats' => [],
    'todaySummary' => null,
    'lastUpdatedLabel' => null,
    'lastUpdatedAt' => null,
    'themeVariant' => 'aurora',
])

@php
    $safeStats = collect($stats)
        ->filter(fn($stat) => is_array($stat) && isset($stat['label'], $stat['value']))
        ->take(4)
        ->values()
        ->all();

    if ($safeStats === []) {
        $safeStats = [
            ['label' => 'Open Incidents', 'value' => 24],
            ['label' => 'Training Completion', 'value' => 91, 'suffix' => '%'],
            ['label' => 'Active Workers', 'value' => 42],
            ['label' => 'Active Sites', 'value' => 18],
        ];
    }

    $variantClasses = [
        'aurora' => [
            'eyebrow' =>
                'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200',
            'secondary' =>
                'hover:border-cyan-300 hover:text-cyan-700 dark:hover:border-cyan-500 dark:hover:text-cyan-300',
            'left_orb' => 'bg-cyan-300/35 dark:bg-cyan-700/30',
            'right_orb' => 'bg-emerald-300/30 dark:bg-emerald-700/30',
            'live_badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
            'progress' => 'from-cyan-500 to-emerald-500',
            'today_box' => 'border-cyan-200/80 bg-cyan-50/70 dark:border-cyan-700/60 dark:bg-cyan-900/20',
            'today_text' => 'text-cyan-700 dark:text-cyan-300',
        ],
        'horizon' => [
            'eyebrow' =>
                'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
            'secondary' => 'hover:border-sky-300 hover:text-sky-700 dark:hover:border-sky-500 dark:hover:text-sky-300',
            'left_orb' => 'bg-sky-300/35 dark:bg-sky-700/30',
            'right_orb' => 'bg-indigo-300/30 dark:bg-indigo-700/30',
            'live_badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
            'progress' => 'from-sky-500 to-indigo-500',
            'today_box' => 'border-sky-200/80 bg-sky-50/70 dark:border-sky-700/60 dark:bg-sky-900/20',
            'today_text' => 'text-sky-700 dark:text-sky-300',
        ],
        'ember' => [
            'eyebrow' =>
                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
            'secondary' =>
                'hover:border-amber-300 hover:text-amber-700 dark:hover:border-amber-500 dark:hover:text-amber-300',
            'left_orb' => 'bg-amber-300/35 dark:bg-amber-700/30',
            'right_orb' => 'bg-rose-300/30 dark:bg-rose-700/30',
            'live_badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200',
            'progress' => 'from-amber-500 to-rose-500',
            'today_box' => 'border-amber-200/80 bg-amber-50/70 dark:border-amber-700/60 dark:bg-amber-900/20',
            'today_text' => 'text-amber-700 dark:text-amber-300',
        ],
    ];

    $classes = $variantClasses[$themeVariant] ?? $variantClasses['aurora'];
@endphp

<section class="relative px-4 pb-16 pt-16 sm:px-6 sm:pb-20 lg:px-8 lg:pt-20" x-data="{
    stats: @js($safeStats),
    display: [],
    started: false,
    init() {
        this.display = this.stats.map(() => 0);

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduceMotion) {
            this.display = this.stats.map((stat) => Number(stat.value) || 0);
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            if (!entries.some((entry) => entry.isIntersecting) || this.started) {
                return;
            }

            this.started = true;
            observer.disconnect();

            const duration = 1200;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                this.display = this.stats.map((stat) => (Number(stat.value) || 0) * progress);

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        }, { threshold: 0.3 });

        observer.observe(this.$el);
    },
    formatValue(index) {
        const stat = this.stats[index] || { value: 0 };
        const suffix = stat.suffix || '';
        return `${Math.round(this.display[index] || 0)}${suffix}`;
    },
}">
    <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2 lg:items-center">
        <x-landing.reveal>
            <div>
                <p
                    class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $classes['eyebrow'] }}">
                    {{ $eyebrow }}</p>
                <h1
                    class="mt-6 max-w-xl font-display text-4xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 sm:text-5xl lg:text-6xl">
                    {{ $title }}</h1>
                <p class="mt-5 max-w-2xl text-base leading-relaxed text-gray-600 dark:text-gray-300 sm:text-lg">
                    {{ $description }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $primaryLink }}"
                        class="inline-flex items-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-gray-900/20 transition duration-300 hover:-translate-y-0.5 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        {{ $primaryText }}
                    </a>
                    <a href="{{ $secondaryLink }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition duration-300 hover:-translate-y-0.5 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 {{ $classes['secondary'] }}">
                        {{ $secondaryText }}
                    </a>
                </div>
            </div>
        </x-landing.reveal>

        <x-landing.reveal delay="120">
            <div class="relative">
                <div class="absolute -left-8 top-10 h-24 w-24 rounded-2xl blur-2xl {{ $classes['left_orb'] }}">
                </div>
                <div class="absolute -right-8 bottom-12 h-28 w-28 rounded-2xl blur-2xl {{ $classes['right_orb'] }}">
                </div>

                <div
                    class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-2xl shadow-gray-200/80 backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/90 dark:shadow-gray-950/40">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                        <p class="font-display text-sm font-semibold text-gray-900 dark:text-gray-100">SHEES Workspace
                        </p>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $classes['live_badge'] }}">Live</span>
                    </div>
                    @if ($lastUpdatedLabel)
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Last updated
                            @if ($lastUpdatedAt)
                                <time datetime="{{ $lastUpdatedAt }}">{{ $lastUpdatedLabel }}</time>
                            @else
                                {{ $lastUpdatedLabel }}
                            @endif
                        </p>
                    @endif

                    <div class="mt-5 space-y-4">
                        <div
                            class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/70">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-300"
                                    x-text="stats[0]?.label || 'Open Incidents'"></p>
                                <p class="text-sm font-semibold text-cyan-700 dark:text-cyan-300"
                                    x-text="formatValue(0)"></p>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-gray-200 dark:bg-gray-600">
                                <div class="h-2 rounded-full bg-gradient-to-r transition-all duration-700 {{ $classes['progress'] }}"
                                    :style="`width: ${Math.max(12, Math.min(100, Number(display[1] || 0)))}%`"></div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/70">
                                <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-300"
                                    x-text="stats[1]?.label || 'Training Completion'"></p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="formatValue(1)"></p>
                            </div>
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/70">
                                <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-300"
                                    x-text="stats[2]?.label || 'Active Workers'"></p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="formatValue(2)"></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border p-4 {{ $classes['today_box'] }}">
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.16em] {{ $classes['today_text'] }}">
                                    Today</p>
                                <p class="text-sm font-semibold {{ $classes['today_text'] }}" x-text="formatValue(3)">
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                                {{ $todaySummary ?? 'Live operational metrics refresh automatically as your SHEES data grows.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-landing.reveal>
    </div>
</section>
