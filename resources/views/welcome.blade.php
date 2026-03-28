<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHEES | Safety, Security & Environmental Enterprise System</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700;space-grotesk:500,600,700&display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon-shees.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const activeTheme = storedTheme || preferredTheme;

            document.documentElement.setAttribute('data-theme', activeTheme);
            document.documentElement.classList.toggle('dark', activeTheme === 'dark');
        })();
    </script>
</head>

<body
    class="bg-white font-body text-gray-800 antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-gray-100"
    x-data="landingPage()" x-init="init()" @keydown.escape.window="closeMobileMenu()">
    @php
        $featureCards = [
            [
                'title' => 'Incident Management',
                'description' =>
                    'Capture incidents, route approvals, and maintain a complete corrective evidence trail.',
                'icon' => '<path d="M12 9v4" /><path d="M12 17h.01" /><path d="M10 2 2 20h20L14 2z" />',
            ],
            [
                'title' => 'Training Management',
                'description' => 'Assign mandatory training and monitor completion with certificate expiry visibility.',
                'icon' => '<path d="m4 6 8-4 8 4-8 4z" /><path d="m4 10 8 4 8-4" /><path d="m4 14 8 4 8-4" />',
            ],
            [
                'title' => 'Inspection Checklists',
                'description' =>
                    'Run mobile-ready checklists and capture evidence for operational safety verification.',
                'icon' => '<path d="M9 11h6" /><path d="M9 15h6" /><path d="M8 3h8l3 3v15H5V6z" />',
            ],
            [
                'title' => 'Site Audits & NCR',
                'description' => 'Track site audits, KPI performance, and non-conformance closure in one workflow.',
                'icon' => '<path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h10" /><path d="m18 16 2 2 3-3" />',
            ],
            [
                'title' => 'Report Builder',
                'description' =>
                    'Generate export-ready operational reports for incidents, audits, and compliance reviews.',
                'icon' =>
                    '<path d="M7 3h8l4 4v14H7z" /><path d="M15 3v5h5" /><path d="M10 13h6" /><path d="M10 17h6" />',
            ],
            [
                'title' => 'Worker Tracking',
                'description' =>
                    'Monitor active workers, attendance logs, and geofence events across operational zones.',
                'icon' =>
                    '<path d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z" /><circle cx="12" cy="11" r="2.5" />',
            ],
        ];

        $workflow = [
            ['Report', 'Frontline teams submit incidents, inspections, and training updates from one platform.'],
            ['Review', 'Managers and safety officers validate findings and prioritize corrective actions.'],
            ['Execute', 'Assigned owners complete actions with timeline tracking and evidence attachments.'],
            ['Optimize', 'Dashboards and reports expose trends that improve compliance and safety outcomes.'],
        ];

        $roles = [
            ['Admin', 'Full system governance and user/permission control.'],
            ['Safety Officer', 'Owns incidents, inspections, and closure quality.'],
            ['Supervisor', 'Manages team actions and completion timelines.'],
            ['Auditor', 'Reviews evidence trail and compliance readiness.'],
            ['Manager', 'Monitors KPIs, trends, and strategic risk.'],
            ['Worker', 'Submits findings and follows assigned actions.'],
        ];

        $benefits = [
            ['Faster Response Time', 'Move from detection to action with structured workflows and clear ownership.'],
            ['Audit-Ready Records', 'Maintain searchable evidence for inspections, legal reviews, and certifications.'],
            ['Reduced Compliance Risk', 'Stay ahead of due dates, expiries, and unresolved corrective actions.'],
            ['Cross-Team Alignment', 'Unify safety, operations, and leadership with one source of truth.'],
        ];

        $heroTheme = request()->query('theme', 'aurora');
        $heroThemeMap = [
            'aurora' => [
                'bg' =>
                    'bg-gradient-to-b from-cyan-100 via-white to-white dark:from-gray-900 dark:via-gray-900 dark:to-gray-900',
                'left_orb' => 'bg-cyan-300/30 dark:bg-cyan-700/20',
                'right_orb' => 'bg-emerald-200/35 dark:bg-emerald-700/20',
                'variant' => 'aurora',
            ],
            'horizon' => [
                'bg' =>
                    'bg-gradient-to-b from-sky-100 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800',
                'left_orb' => 'bg-sky-300/30 dark:bg-sky-700/20',
                'right_orb' => 'bg-indigo-200/35 dark:bg-indigo-700/20',
                'variant' => 'horizon',
            ],
            'ember' => [
                'bg' =>
                    'bg-gradient-to-b from-amber-100 via-white to-rose-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800',
                'left_orb' => 'bg-amber-300/30 dark:bg-amber-700/20',
                'right_orb' => 'bg-rose-200/35 dark:bg-rose-700/20',
                'variant' => 'ember',
            ],
        ];
        $selectedHeroTheme = $heroThemeMap[$heroTheme] ?? $heroThemeMap['aurora'];
    @endphp

    <div class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[28rem] {{ $selectedHeroTheme['bg'] }}">
        </div>
        <div
            class="pointer-events-none absolute -left-24 top-20 -z-10 h-72 w-72 rounded-full blur-3xl {{ $selectedHeroTheme['left_orb'] }}">
        </div>
        <div
            class="pointer-events-none absolute -right-24 top-16 -z-10 h-80 w-80 rounded-full blur-3xl {{ $selectedHeroTheme['right_orb'] }}">
        </div>

        <header
            class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/85 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-900/85">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}"
                    class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-gray-900 dark:text-gray-100">SHEES</a>

                <nav class="hidden items-center gap-2 text-sm font-medium md:flex">
                    <a href="#features"
                        class="rounded-xl px-3 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100">Features</a>
                    <a href="#how-it-works"
                        class="rounded-xl px-3 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100">How
                        It Works</a>
                    <a href="#roles"
                        class="rounded-xl px-3 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100">Roles</a>
                </nav>

                <div class="flex items-center gap-2">
                    <button @click="toggleMobileMenu()" type="button" aria-label="Toggle menu"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100 md:hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg x-show="!mobileMenu" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18" />
                            <path d="M3 12h18" />
                            <path d="M3 18h18" />
                        </svg>
                        <svg x-show="mobileMenu" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="m18 6-12 12" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                    <button @click="toggleTheme()" type="button" aria-label="Toggle theme"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <span x-show="theme === 'light'" x-cloak>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21 12.79A9 9 0 1 1 11.21 3c0 .28-.01.56-.01.85A8 8 0 0 0 20.15 12c.29 0 .57-.01.85-.03Z" />
                            </svg>
                        </span>
                        <span x-show="theme === 'dark'" x-cloak>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2" />
                                <path d="M12 20v2" />
                                <path d="m4.93 4.93 1.41 1.41" />
                                <path d="m17.66 17.66 1.41 1.41" />
                                <path d="M2 12h2" />
                                <path d="M20 12h2" />
                                <path d="m6.34 17.66-1.41 1.41" />
                                <path d="m19.07 4.93-1.41 1.41" />
                            </svg>
                        </span>
                    </button>
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        Login
                    </a>
                </div>
            </div>

            <div x-show="mobileMenu" x-cloak x-transition.opacity
                class="border-t border-gray-200 bg-white/95 px-4 py-4 backdrop-blur-xl md:hidden dark:border-gray-700 dark:bg-gray-900/95">
                <nav class="grid gap-2 text-sm font-medium">
                    <a @click="closeMobileMenu()" href="#features"
                        class="rounded-xl px-3 py-2 text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Features</a>
                    <a @click="closeMobileMenu()" href="#how-it-works"
                        class="rounded-xl px-3 py-2 text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">How
                        It Works</a>
                    <a @click="closeMobileMenu()" href="#roles"
                        class="rounded-xl px-3 py-2 text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Roles</a>
                    <a @click="closeMobileMenu()" href="#benefits"
                        class="rounded-xl px-3 py-2 text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Benefits</a>
                </nav>
            </div>
        </header>

        <x-hero title="Safety Operations, Elevated" eyebrow="Safety, Security & Environmental Enterprise System"
            description="A premium control center for incidents, training, inspections, and workforce visibility. Built to keep every site safer, faster, and audit-ready."
            primary-text="Login" primary-link="{{ route('login') }}" secondary-text="Explore Features"
            secondary-link="#features" :stats="$landingMetrics['stats'] ?? []" :today-summary="$landingMetrics['today_summary'] ?? null" :last-updated-label="$landingMetrics['last_updated_label'] ?? null" :last-updated-at="$landingMetrics['last_updated_at'] ?? null"
            :theme-variant="$selectedHeroTheme['variant']" />
    </div>

    <x-section id="features" eyebrow="Core Platform" title="Everything you need in one operational workflow"
        description="Purpose-built modules that connect frontline reporting to management visibility without fragmented tools.">
        <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($featureCards as $index => $feature)
                <x-landing.reveal :delay="($index + 1) * 70">
                    <x-feature-card :title="$feature['title']" :description="$feature['description']">
                        <x-slot:icon>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                {!! $feature['icon'] !!}
                            </svg>
                        </x-slot:icon>
                    </x-feature-card>
                </x-landing.reveal>
            @endforeach
        </div>
    </x-section>

    <x-section id="how-it-works" wrapper-class="bg-gray-50/90 dark:bg-gray-800/70" eyebrow="How It Works"
        title="A clean path from report to resolution"
        description="A timeline-driven process that keeps every stakeholder aligned from first signal to measurable improvement.">
        <div class="mt-12 grid gap-6 lg:grid-cols-4">
            @foreach ($workflow as $index => $step)
                <x-landing.reveal :delay="($index + 1) * 65">
                    <article
                        class="relative rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800">
                        <span
                            class="absolute -top-3 left-6 rounded-full border border-cyan-200 bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700 dark:border-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200">Step
                            {{ $index + 1 }}</span>
                        <h3 class="mt-4 font-display text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $step[0] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $step[1] }}
                        </p>
                    </article>
                </x-landing.reveal>
            @endforeach
        </div>
    </x-section>

    <x-section id="roles" eyebrow="Role-Based Access" title="Designed for every responsibility tier"
        description="Granular access keeps data secure while giving each role a tailored workspace for faster execution.">
        <div class="mt-12 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($roles as $index => $role)
                <x-landing.reveal :delay="($index + 1) * 45">
                    <article
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <p
                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            {{ $role[0] }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $role[1] }}
                        </p>
                    </article>
                </x-landing.reveal>
            @endforeach
        </div>
    </x-section>

    <x-section id="benefits" wrapper-class="bg-gray-900" title-class="text-gray-100"
        description-class="text-gray-300" eyebrow-class="text-cyan-300" eyebrow="Value"
        title="Why teams choose SHEES"
        description="Operational clarity, lower compliance risk, and stronger safety culture through one integrated platform.">
        <div class="mt-12 grid gap-5 md:grid-cols-2">
            @foreach ($benefits as $index => $benefit)
                <x-landing.reveal :delay="($index + 1) * 55">
                    <article
                        class="group rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm transition duration-300 hover:border-cyan-300/50 hover:bg-white/10">
                        <div
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/20 text-cyan-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="m5 12 4 4 10-10" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-display text-xl font-semibold text-white">{{ $benefit[0] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-300">{{ $benefit[1] }}</p>
                    </article>
                </x-landing.reveal>
            @endforeach
        </div>
    </x-section>

    <section class="px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <x-landing.reveal class="mx-auto max-w-5xl">
            <div
                class="rounded-3xl border border-cyan-200/70 bg-gradient-to-br from-cyan-100 via-white to-emerald-100 p-8 text-center shadow-xl shadow-cyan-100/70 dark:border-cyan-700/60 dark:from-gray-800 dark:via-gray-800 dark:to-gray-700 dark:shadow-cyan-900/30 sm:p-12">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Ready To
                    Modernize Safety Ops?</p>
                <h2
                    class="mt-4 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 sm:text-4xl">
                    Move from reactive tracking to proactive control.</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base text-gray-600 dark:text-gray-300">Launch your workspace and
                    give every team member a smarter, cleaner, and faster way to execute safety workflows.</p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        Access Workspace
                    </a>
                    <a href="#how-it-works"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:-translate-y-0.5 hover:border-cyan-300 hover:text-cyan-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-cyan-500 dark:hover:text-cyan-300">
                        See Workflow
                    </a>
                </div>
            </div>
        </x-landing.reveal>
    </section>

    <x-footer />

</body>

</html>
