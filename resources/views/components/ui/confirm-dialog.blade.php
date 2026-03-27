@props([
    'title' => 'Please confirm',
    'message' => 'Are you sure you want to continue?',
    'action',
    'method' => 'POST',
    'triggerLabel' => 'Confirm',
    'triggerVariant' => 'secondary',
    'confirmLabel' => 'Confirm',
    'confirmVariant' => 'danger',
    'cancelLabel' => 'Cancel',
])

<x-ui.modal :title="$title" :description="$message" maxWidth="max-w-md">
    <x-slot:trigger>
        <x-ui.button :variant="$triggerVariant" size="md">
            {{ $triggerLabel }}
        </x-ui.button>
    </x-slot:trigger>

    <x-slot:actions>
        <x-ui.button variant="secondary" @click="open = false">{{ $cancelLabel }}</x-ui.button>

        <form method="POST" action="{{ $action }}">
            @csrf
            @if (!in_array(strtoupper($method), ['GET', 'POST'], true))
                @method($method)
            @endif

            <x-ui.button type="submit" :variant="$confirmVariant">
                {{ $confirmLabel }}
            </x-ui.button>
        </form>
    </x-slot:actions>
</x-ui.modal>
