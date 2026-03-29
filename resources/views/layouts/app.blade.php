<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const activeTheme = storedTheme === 'dark' || storedTheme === 'light' ? storedTheme : 'light';

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
            class="pointer-events-none fixed inset-0 -z-10 bg-[linear-gradient(180deg,#f7fafc_0%,#f2f5f8_100%)] dark:bg-[linear-gradient(180deg,#0b1220_0%,#111827_100%)]">
        </div>

        <x-layout.navbar />

        <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/50 dark:bg-slate-950/70 lg:hidden"
            @click="sidebarOpen = false"></div>

        <aside x-cloak x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto p-4 lg:hidden">
            <x-layout.sidebar />
        </aside>

        <div class="mx-auto flex w-full max-w-[1500px] gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <aside class="hidden w-72 shrink-0 lg:block">
                <x-layout.sidebar />
            </aside>

            <main class="min-w-0 flex-1">
                @if (isset($header))
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @elseif (View::hasSection('header'))
                    <div class="mb-6">
                        @yield('header')
                    </div>
                @endif

                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>

        <x-ui.toast-center :toasts="session('toast') ? [session('toast')] : []" />
    </div>
</body>

</html>
