<footer class="border-t border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/80">
    <div
        class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-gray-300">
        <p>&copy; {{ now()->year }} SHEES. All rights reserved.</p>

        <nav class="flex flex-wrap items-center gap-4">
            <a href="{{ route('landing') }}" class="transition hover:text-cyan-700 dark:hover:text-cyan-300">Home</a>
            <a href="#features" class="transition hover:text-cyan-700 dark:hover:text-cyan-300">Features</a>
            <a href="#how-it-works" class="transition hover:text-cyan-700 dark:hover:text-cyan-300">How It Works</a>
            <a href="{{ route('login') }}" class="transition hover:text-cyan-700 dark:hover:text-cyan-300">Login</a>
        </nav>
    </div>
</footer>
