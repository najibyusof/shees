@props([
    'toasts' => [],
])

<div x-data="{
    toasts: @js($toasts),
    push(toast) {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, timeout: 3500, ...toast });
        setTimeout(() => this.remove(id), toast.timeout ?? 3500);
    },
    remove(id) {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
    }
}" @toast.window="push($event.detail)"
    class="pointer-events-none fixed right-4 top-4 z-[80] flex w-full max-w-sm flex-col gap-2 sm:right-6 sm:top-6">

    <template x-for="toast in toasts" :key="toast.id">
        <div class="pointer-events-auto rounded-xl border px-4 py-3 shadow-lg"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-200': (
                    toast.type || 'info') === 'success',
                'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-200': toast
                    .type === 'error',
                'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200': toast
                    .type === 'warning',
                'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-900/50 dark:bg-sky-900/20 dark:text-sky-200': !
                    toast.type || toast.type === 'info',
            }">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold" x-text="toast.title || 'Notification'"></p>
                    <p class="mt-0.5 text-sm" x-text="toast.message || ''"></p>
                </div>
                <button type="button" @click="remove(toast.id)"
                    class="text-xs font-semibold opacity-80 hover:opacity-100">Close</button>
            </div>
        </div>
    </template>
</div>
