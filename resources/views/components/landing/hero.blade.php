@props([
    'title' => 'SHEES',
    'tagline' => 'Safety, Security & Environmental Enterprise System',
    'description' => '',
])

<section class="relative overflow-hidden"
    x-data="{
        activeGradient: 0,
        counters: { incidents: 0, training: 0, zones: 0 },
        targets: { incidents: 24, training: 91, zones: 4 },
        started: false,
        init() {
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                setInterval(() => {
                    this.activeGradient = (this.activeGradient + 1) % 3;
                }, 4200);
            }

            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting) || this.started) {
                    return;
                }

                this.started = true;
                observer.disconnect();
                this.animateCounters();
            }, { threshold: 0.35 });

            observer.observe(this.$el);
        },
        animateCounters() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.counters = { ...this.targets };
                return;
            }

            const duration = 1300;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                this.counters.incidents = this.targets.incidents * progress;
                this.counters.training = this.targets.training * progress;
                this.counters.zones = this.targets.zones * progress;

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        },
    }">
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-b from-cyan-100 via-white to-white transition-opacity duration-[1800ms] dark:from-gray-900 dark:via-gray-900 dark:to-gray-900"
            :class="activeGradient === 0 ? 'opacity-100' : 'opacity-0'"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-teal-100 via-white to-cyan-50 transition-opacity duration-[1800ms] dark:from-gray-900 dark:via-gray-900 dark:to-gray-800"
            :class="activeGradient === 1 ? 'opacity-100' : 'opacity-0'"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-sky-100 via-white to-emerald-50 transition-opacity duration-[1800ms] dark:from-gray-900 dark:via-gray-900 dark:to-gray-800"
            :class="activeGradient === 2 ? 'opacity-100' : 'opacity-0'"></div>
    </div>
    <div class="absolute -left-24 top-6 -z-10 h-64 w-64 rounded-full bg-cyan-300/30 blur-3xl motion-safe:animate-pulse"></div>
    <div class="absolute -right-16 bottom-0 -z-10 h-72 w-72 rounded-full bg-teal-300/30 blur-3xl motion-safe:animate-pulse"></div>

    <div class="mx-auto grid max-w-7xl gap-12 px-6 pb-20 pt-24 lg:grid-cols-2 lg:items-center">
        <div>
            <p class="inline-flex items-center rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-teal-700 dark:border-teal-700 dark:bg-teal-900/30 dark:text-teal-200">
                {{ $tagline }}
            </p>
            <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-gray-100 sm:text-5xl lg:text-6xl">{{ $title }}</h1>
            <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 dark:text-gray-300 sm:text-lg">{{ $description }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}"
                    class="inline-flex items-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                    Login
                </a>
                <a href="#features"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-teal-300 hover:text-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-teal-500 dark:hover:text-teal-300">
                    View Features
                </a>
            </div>
        </div>

        <div class="relative">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl shadow-cyan-200/40 dark:border-gray-700 dark:bg-gray-800 dark:shadow-cyan-900/20">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-700/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-300">Incidents</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-gray-100" x-text="Math.round(counters.incidents)"></p>
                        <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-300">8 resolved this week</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-700/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-300">Training</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-gray-100"><span x-text="Math.round(counters.training)"></span>%</p>
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">Compliance completion</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-700/70 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-300">Live Monitoring</p>
                        <div class="mt-3 flex items-end gap-2">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <p class="text-sm text-slate-700 dark:text-gray-200">Worker tracking active across <span x-text="Math.round(counters.zones)"></span> operational zones.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
