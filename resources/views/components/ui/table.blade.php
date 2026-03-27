@props([
    'empty' => 'No records found.',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 ui-border dark:border-gray-700']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 bg-white text-sm ui-border ui-surface dark:divide-gray-700 dark:bg-gray-800">
            @isset($head)
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 ui-surface-soft ui-text-muted dark:bg-gray-800 dark:text-gray-300">
                    {{ $head }}
                </thead>
            @endisset

            <tbody class="divide-y divide-gray-200 bg-white text-gray-600 ui-border ui-text-muted dark:divide-gray-700 dark:bg-gray-800 dark:text-gray-300 [&>tr:hover]:bg-gray-50 dark:[&>tr:hover]:bg-gray-700">
                @if (trim((string) $slot) !== '')
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="100" class="px-4 py-6 text-center text-sm ui-text-muted">{{ $empty }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
