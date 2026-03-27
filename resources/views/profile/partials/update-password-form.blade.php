<x-ui.card title="Update Password" subtitle="Use a strong password to protect your account and approvals.">

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <x-ui.form-input id="update_password_current_password" name="current_password" type="password"
                label="Current Password" autocomplete="current-password" />
            @if ($errors->updatePassword->has('current_password'))
                <p class="mt-1.5 text-sm text-rose-600">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <x-ui.form-input id="update_password_password" name="password" type="password" label="New Password"
                autocomplete="new-password" />
            @if ($errors->updatePassword->has('password'))
                <p class="mt-1.5 text-sm text-rose-600">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <x-ui.form-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                label="Confirm Password" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password_confirmation'))
                <p class="mt-1.5 text-sm text-rose-600">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">Update Password</x-ui.button>

            @if (session('status') === 'password-updated')
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
