<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-gray-100">Welcome Back</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-gray-300">Sign in to continue managing safety operations.</p>
    </div>

    <x-auth-session-status
        class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200"
        :status="session('status')" />

    @if ($errors->any())
        <div
            class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm text-slate-700 dark:text-gray-200" />
            <div class="relative mt-1">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16v12H4z" />
                        <path d="m4 7 8 6 8-6" />
                    </svg>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username"
                    required autofocus
                    class="relative z-10 block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-teal-400 dark:focus:ring-teal-400" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm text-slate-700 dark:text-gray-200" />
            <div class="relative mt-1">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="11" width="16" height="10" rx="2" />
                        <path d="M8 11V8a4 4 0 1 1 8 0v3" />
                    </svg>
                </span>
                <input id="password" type="password" name="password" autocomplete="current-password" required
                    data-password-input
                    class="relative z-10 block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-11 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-teal-400 dark:focus:ring-teal-400" />
                <button type="button" data-password-toggle aria-label="Toggle password visibility"
                    class="absolute inset-y-0 right-0 z-20 flex items-center pr-3 text-slate-400 transition hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500/40 dark:text-gray-400 dark:hover:text-gray-200">
                    <span data-password-icon-show>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </span>
                    <span data-password-icon-hide class="hidden">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path d="m3 3 18 18" />
                            <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                            <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.8 21.8 0 0 1-4.22 5.35" />
                            <path d="M6.61 6.61A22.52 22.52 0 0 0 1 12s4 7 11 7a10.94 10.94 0 0 0 4.21-.83" />
                        </svg>
                    </span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-col items-start justify-between gap-2 pt-1 sm:flex-row sm:items-center">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800 dark:text-teal-400">
                <span class="ms-2 text-sm text-slate-600 dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-slate-600 underline underline-offset-4 transition hover:text-teal-700 dark:text-gray-300 dark:hover:text-teal-300"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center rounded-xl px-5 py-3 text-[11px] focus:ring-teal-500">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 rounded-xl border border-teal-200 bg-teal-50 p-4 sm:p-5 dark:border-teal-700 dark:bg-teal-900/30">
        <h3 class="text-sm font-semibold text-teal-900 dark:text-teal-100">Demo Accounts</h3>
        <p class="mt-1 text-xs text-teal-700 dark:text-teal-200">Email and role mapping for demo login. Password for all
            accounts: <span class="font-semibold">password</span></p>

        <div class="mt-3 overflow-hidden rounded-lg border border-teal-200 dark:border-teal-700">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-teal-100 text-teal-900 dark:bg-teal-900/50 dark:text-teal-100">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Role</th>
                        <th class="px-3 py-2 font-semibold">Email</th>
                        <th class="px-3 py-2 font-semibold">Password</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-teal-200 bg-white text-teal-900 dark:divide-teal-800 dark:bg-slate-900 dark:text-teal-100">
                    <tr>
                        <td class="px-3 py-2">Admin</td>
                        <td class="px-3 py-2">admin@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Manager</td>
                        <td class="px-3 py-2">manager@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Safety Officer</td>
                        <td class="px-3 py-2">safety@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Auditor</td>
                        <td class="px-3 py-2">auditor@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Supervisor</td>
                        <td class="px-3 py-2">supervisor@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">Worker</td>
                        <td class="px-3 py-2">worker@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">HOD HSSE</td>
                        <td class="px-3 py-2">hod@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">APSB PD</td>
                        <td class="px-3 py-2">pd@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2">MRTS</td>
                        <td class="px-3 py-2">mrts@example.com</td>
                        <td class="px-3 py-2">password</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-center text-sm text-slate-600 dark:text-gray-300">
        New to SHEES?
        <a href="{{ route('register') }}"
            class="font-semibold text-teal-700 underline underline-offset-4 transition hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200">
            Create an account
        </a>
    </p>
</x-guest-layout>
