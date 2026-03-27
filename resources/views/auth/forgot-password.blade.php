<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-gray-100">Forgot Password</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-gray-300">
            {{ __('Enter your email address and we will send a password reset link.') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-auth.input-field id="email" name="email" :label="__('Email')" type="email" :value="old('email')" required autofocus>
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16v12H4z" />
                    <path d="m4 7 8 6 8-6" />
                </svg>
            </x-slot:icon>
        </x-auth.input-field>

        <div class="flex items-center justify-end pt-2">
            <x-primary-button class="rounded-xl px-5 py-3 text-[11px] focus:ring-teal-500">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
