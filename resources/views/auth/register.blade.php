<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-gray-100">Create Account</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-gray-300">Set up your secure SHEES access profile.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-auth.input-field id="name" name="name" :label="__('Name')" type="text" :value="old('name')" autocomplete="name" required autofocus>
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21a8 8 0 0 0-16 0" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </x-slot:icon>
        </x-auth.input-field>

        <x-auth.input-field id="email" name="email" :label="__('Email')" type="email" :value="old('email')" autocomplete="username" required>
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16v12H4z" />
                    <path d="m4 7 8 6 8-6" />
                </svg>
            </x-slot:icon>
        </x-auth.input-field>

        <x-auth.input-field id="password" name="password" :label="__('Password')" type="password" autocomplete="new-password" required toggleable>
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="11" width="16" height="10" rx="2" />
                    <path d="M8 11V8a4 4 0 1 1 8 0v3" />
                </svg>
            </x-slot:icon>
        </x-auth.input-field>

        <x-auth.input-field id="password_confirmation" name="password_confirmation" :label="__('Confirm Password')" type="password" autocomplete="new-password" required toggleable>
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 12 2 2 4-4" />
                    <rect x="4" y="4" width="16" height="16" rx="2" />
                </svg>
            </x-slot:icon>
        </x-auth.input-field>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm font-medium text-slate-600 underline underline-offset-4 transition hover:text-teal-700 dark:text-gray-300 dark:hover:text-teal-300"
                href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="rounded-xl px-5 py-3 text-[11px] focus:ring-teal-500">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
