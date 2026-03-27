<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHEES | Safety, Security & Environmental Enterprise System</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;instrument-sans:500,600,700&display=swap"
        rel="stylesheet" />

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

<body class="bg-white text-slate-900 antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-gray-100" x-data="landingPage()"
    x-init="init()">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-lg dark:border-gray-700 dark:bg-gray-800/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('landing') }}" class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700 dark:text-teal-300">SHEES</a>
            <nav class="flex items-center gap-3 text-sm font-medium">
                <a href="#features" class="hidden rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-100 hover:text-teal-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-teal-300 sm:inline-flex">Features</a>
                <a href="#workflow" class="hidden rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-100 hover:text-teal-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-teal-300 sm:inline-flex">How It Works</a>
                <button @click="toggleTheme()" type="button" aria-label="Toggle theme"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <span x-show="theme === 'light'" x-cloak>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3c0 .28-.01.56-.01.85A8 8 0 0 0 20.15 12c.29 0 .57-.01.85-.03Z" />
                        </svg>
                    </span>
                    <span x-show="theme === 'dark'" x-cloak>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    class="rounded-lg bg-teal-600 px-4 py-2 text-white transition hover:bg-teal-500">Login</a>
            </nav>
        </div>
    </header>

    <x-landing.hero title="SHEES"
        tagline="Safety, Security & Environmental Enterprise System"
        description="A unified platform to manage incidents, training, inspections, audits, and worker tracking with transparent workflows and role-based accountability." />

    <section id="features" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20">
        <x-landing.reveal>
            <x-landing.section-title eyebrow="Platform Modules" title="Core Operational Capabilities"
                description="Everything safety and environmental teams need in one consistent workflow, built for rapid action and long-term compliance." />
        </x-landing.reveal>

        <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <x-landing.reveal delay="70">
                <x-landing.feature-card title="Incident Management"
                    description="Capture incidents instantly, route approvals, and preserve complete investigation and closure history.">
                    <x-slot:icon>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                            <path d="M10 2 2 20h20L14 2z" />
                        </svg>
                    </x-slot:icon>
                </x-landing.feature-card>
            </x-landing.reveal>

            <x-landing.reveal delay="120">
                <x-landing.feature-card title="Training System"
                    description="Assign mandatory training, monitor completion, and track certificate expiry before compliance gaps appear.">
                    <x-slot:icon>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m4 6 8-4 8 4-8 4z" />
                            <path d="m4 10 8 4 8-4" />
                            <path d="m4 14 8 4 8-4" />
                        </svg>
                    </x-slot:icon>
                </x-landing.feature-card>
            </x-landing.reveal>

            <x-landing.reveal delay="170">
                <x-landing.feature-card title="Inspection & Audit"
                    description="Execute checklists, collect NCRs, and deliver audit-ready evidence with corrective action follow-up.">
                    <x-slot:icon>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11h6" />
                            <path d="M9 15h6" />
                            <path d="M8 3h8l3 3v15H5V6z" />
                        </svg>
                    </x-slot:icon>
                </x-landing.feature-card>
            </x-landing.reveal>

            <x-landing.reveal delay="220">
                <x-landing.feature-card title="Worker Tracking"
                    description="Monitor worker presence and geofence adherence in near real-time for safer field operations.">
                    <x-slot:icon>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                    </x-slot:icon>
                </x-landing.feature-card>
            </x-landing.reveal>
        </div>
    </section>

    <section id="workflow" class="bg-slate-50/80 py-16 dark:bg-gray-800/60 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <x-landing.reveal>
                <x-landing.section-title eyebrow="Operational Flow" title="How SHEES Works"
                    description="A streamlined process from first report to sustained improvement." />
            </x-landing.reveal>

            <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Report', 'Frontline teams report incidents, hazards, and findings with evidence.'], ['Review', 'Safety reviewers and approvers validate severity, controls, and priority.'], ['Action', 'Corrective actions are assigned, tracked, and verified with accountability.'], ['Monitor', 'Dashboards provide live status, trends, and compliance readiness.']] as $step => $item)
                    <x-landing.reveal :delay="($step + 1) * 55">
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-teal-600">Step {{ $step + 1 }}</p>
                            <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $item[0] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-gray-300">{{ $item[1] }}</p>
                        </article>
                    </x-landing.reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20">
        <x-landing.reveal>
            <x-landing.section-title eyebrow="Access Control" title="Role-Based Access"
                description="SHEES enforces permission-aware workflows so every role sees only what they need." />
        </x-landing.reveal>

        <div class="mt-10 flex flex-wrap justify-center gap-3">
            @foreach (['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'] as $index => $role)
                <x-landing.reveal as="span" class="inline-flex" :delay="($index + 1) * 45">
                    <span class="rounded-full border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-700 dark:bg-teal-900/30 dark:text-teal-200 dark:hover:bg-teal-900/50">{{ $role }}</span>
                </x-landing.reveal>
            @endforeach
        </div>
    </section>

    <section class="bg-slate-900 py-16 text-slate-100 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <x-landing.reveal>
                <x-landing.section-title eyebrow="Why Teams Choose SHEES" title="System Benefits" center="false"
                    description="Built to improve safety outcomes while reducing reporting friction and compliance risk."
                    class="text-white [&_h2]:text-white [&_p]:text-slate-300 [&_p:first-child]:text-cyan-300" />
            </x-landing.reveal>

            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Compliance Tracking', 'Stay aligned with policies, training due dates, and closure obligations.'], ['Real-Time Monitoring', 'Track operational signals and workforce movement as events happen.'], ['Audit Readiness', 'Retrieve complete records for inspections and management reviews.'], ['Safety Improvement', 'Turn findings into actions with measurable continuous improvement.']] as $index => $benefit)
                    <x-landing.reveal :delay="($index + 1) * 55">
                        <article class="rounded-2xl border border-white/15 bg-white/5 p-6 transition hover:border-cyan-300/40 hover:bg-white/10">
                            <h3 class="text-lg font-semibold text-white">{{ $benefit[0] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $benefit[1] }}</p>
                        </article>
                    </x-landing.reveal>
                @endforeach
            </div>

            <x-landing.reveal delay="220" class="mt-12 inline-block">
                <a href="{{ route('login') }}"
                    class="inline-flex items-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:bg-cyan-300">
                    Access Your Workspace
                </a>
            </x-landing.reveal>
        </div>
    </section>

    <x-landing.footer />

</body>

</html>
