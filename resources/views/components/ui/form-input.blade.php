@props(['name', 'label' => null, 'type' => 'text', 'value' => '', 'placeholder' => '', 'required' => false])

@php
    $id = $attributes->get('id', $name);
    $inputValue = old($name, $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium ui-text-muted">
            {{ $label }}
        </label>
    @endif

    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $inputValue }}"
        placeholder="{{ $placeholder }}" @required($required)
        {{ $attributes->except('class')->merge(['class' => 'w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 dark:placeholder:text-slate-500 dark:focus:border-teal-400 dark:focus:ring-teal-500/40']) }}>

    @error($name)
        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
