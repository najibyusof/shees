<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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

<body class="relative min-h-screen overflow-x-hidden ui-bg ui-text font-sans antialiased transition-colors duration-300"
    x-data="{
        theme: document.documentElement.getAttribute('data-theme') || 'light',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', this.theme);
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
            localStorage.setItem('theme', this.theme);
        }
    }">
    <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.2),_transparent_42%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.2),_transparent_46%)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.28),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(6,182,212,0.2),_transparent_46%)]">
    </div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-gray-200/70 bg-white/90 shadow-2xl backdrop-blur-xl transition-colors duration-300 dark:border-gray-700/80 dark:bg-gray-800/90 sm:rounded-3xl lg:grid-cols-2">
            <div class="hidden border-r border-gray-200/60 bg-gradient-to-br from-teal-700 to-cyan-700 p-8 text-gray-100 dark:border-gray-700 dark:from-gray-900 dark:to-teal-900 xl:p-10 lg:block">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-sm font-semibold tracking-wide text-cyan-100 dark:text-cyan-300">
                    SHEES
                </a>
                <h1 class="mt-5 text-3xl font-semibold leading-tight text-white dark:text-gray-100">
                    Safety, Security & Environmental Enterprise System
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-teal-50/90 dark:text-gray-300">
                    Manage incidents, compliance, training, audits, and workforce tracking from one reliable platform.
                </p>

                <div class="mt-8 space-y-3 text-sm text-teal-50/90 dark:text-gray-300">
                    <p class="rounded-xl border border-cyan-200/30 bg-white/10 px-4 py-3 dark:border-cyan-500/30 dark:bg-cyan-500/10">Incident response workflows with manager approvals.</p>
                    <p class="rounded-xl border border-cyan-200/30 bg-white/10 px-4 py-3 dark:border-cyan-500/30 dark:bg-cyan-500/10">Training validity and certificate compliance monitoring.</p>
                    <p class="rounded-xl border border-cyan-200/30 bg-white/10 px-4 py-3 dark:border-cyan-500/30 dark:bg-cyan-500/10">Inspection, audit, and real-time worker visibility.</p>
                </div>
            </div>

            <div class="bg-white p-5 transition-colors duration-300 dark:bg-gray-800 sm:p-10">
                <div class="mb-6 flex items-center justify-between gap-3">
                    <a href="{{ route('landing') }}" class="text-sm font-semibold text-slate-700 transition hover:text-teal-700 dark:text-gray-200 dark:hover:text-teal-300">Back to Home</a>
                    <div class="flex items-center gap-2">
                        <button @click="toggleTheme()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700"
                            type="button" aria-label="Toggle theme">
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
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Secure Access</span>
                    </div>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var wrapper = button.closest('div.relative');
                    if (!wrapper) {
                        return;
                    }

                    var input = wrapper.querySelector('[data-password-input]');
                    if (!input) {
                        return;
                    }

                    var isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';

                    var showIcon = button.querySelector('[data-password-icon-show]');
                    var hideIcon = button.querySelector('[data-password-icon-hide]');

                    if (showIcon && hideIcon) {
                        showIcon.classList.toggle('hidden', isHidden);
                        hideIcon.classList.toggle('hidden', !isHidden);
                    }
                });
            });
        });
    </script>
</body>

</html>
