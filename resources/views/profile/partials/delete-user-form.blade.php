<x-ui.card title="Delete Account" subtitle="Permanently remove your account and all associated records.">
    <div class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-900 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-100">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
    </div>

    <div class="mt-4">
        <x-ui.button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" variant="danger">
            Delete Account
        </x-ui.button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-5 p-6 sm:p-7">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold ui-text">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="text-sm ui-text-muted">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div>
                <label for="password" class="sr-only">{{ __('Password') }}</label>

                <input id="password" name="password" type="password" placeholder="{{ __('Password') }}"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 dark:placeholder:text-slate-500 dark:focus:border-teal-400 dark:focus:ring-teal-500/40 sm:w-3/4" />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button x-on:click="$dispatch('close')" variant="secondary" type="button">
                    {{ __('Cancel') }}
                </x-ui.button>

                <x-ui.button variant="danger" type="submit">
                    {{ __('Delete Account') }}
                </x-ui.button>
            </div>
        </form>
    </x-modal>
</x-ui.card>
