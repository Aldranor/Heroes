@props([
    'name',
    'value',
    'label',
    'description' => null,
    'checked' => false,
])

@php
    $id = str($name.'-'.$value)->slug('-');
@endphp

<label for="{{ $id }}" class="block cursor-pointer">
    <input
        id="{{ $id }}"
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        class="peer sr-only"
        @checked($checked)
        {{ $attributes->except('class') }}
    >

    <span class="ob-variant-card">
        <span class="flex flex-col items-center gap-2">
            {{ $slot }}
            <span class="space-y-0.5 text-center">
                <span class="block text-sm font-semibold text-slate-900">{{ $label }}</span>
                @if ($description)
                    <span class="block text-xs text-stone-500">{{ $description }}</span>
                @endif
            </span>
        </span>
    </span>
</label>
