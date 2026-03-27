<footer class="border-t border-slate-200 bg-slate-50/70 dark:border-gray-700 dark:bg-gray-800/70">
    <div
        class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-8 text-sm text-slate-600 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between">
        <p>&copy; {{ now()->year }} SHEES. All rights reserved.</p>
        <nav class="flex items-center gap-4">
            <a href="{{ route('login') }}" class="transition hover:text-teal-700 dark:hover:text-teal-300">Login</a>
            <a href="#features" class="transition hover:text-teal-700 dark:hover:text-teal-300">Features</a>
            <a href="mailto:contact@shees.local" class="transition hover:text-teal-700 dark:hover:text-teal-300">Contact</a>
        </nav>
    </div>
</footer>
