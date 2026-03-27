<x-ui.card title="Profile Information" subtitle="Update your name and email address used across the system.">

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <x-ui.form-input name="name" label="Name" :value="old('name', $user->name)" required autofocus
            autocomplete="name" />

        <div class="space-y-2">
            <x-ui.form-input name="email" type="email" label="Email" :value="old('email', $user->email)" required
                autocomplete="username" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                    <p>
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                            class="ml-1 underline decoration-amber-500/70 underline-offset-2 transition hover:text-amber-700 dark:hover:text-amber-100">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-emerald-700 dark:text-emerald-300"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-ui.card>
