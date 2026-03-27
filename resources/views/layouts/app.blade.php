<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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

<body class="ui-bg ui-text antialiased transition-colors duration-300">
    <div class="min-h-screen" x-data="{
        sidebarOpen: false,
        theme: document.documentElement.getAttribute('data-theme') || 'light',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', this.theme);
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
            localStorage.setItem('theme', this.theme);
        }
    }">
        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(15,118,110,0.18),_transparent_42%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.18),_transparent_48%)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.22),_transparent_45%),radial-gradient(circle_at_bottom_right,_rgba(8,145,178,0.2),_transparent_48%)]">
        </div>

        <x-layout.navbar />

        <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/50 dark:bg-slate-950/70 lg:hidden"
            @click="sidebarOpen = false"></div>

        <aside x-cloak x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto border-r border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 lg:hidden">
            <x-layout.sidebar />
        </aside>

        <div class="mx-auto flex w-full max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <aside class="hidden w-64 shrink-0 lg:block">
                <x-layout.sidebar />
            </aside>

            <main class="min-w-0 flex-1">
                @if (isset($header))
                    <div class="mb-6 rounded-2xl border ui-border ui-surface p-5 shadow-sm">
                        {{ $header }}
                    </div>
                @elseif (View::hasSection('header'))
                    <div class="mb-6 rounded-2xl border ui-border ui-surface p-5 shadow-sm">
                        @yield('header')
                    </div>
                @endif

                <div class="rounded-2xl border ui-border ui-surface p-6 shadow-sm sm:p-7">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </div>
            </main>
        </div>

        <x-ui.toast-center :toasts="session('toast') ? [session('toast')] : []" />
    </div>
</body>

</html>
