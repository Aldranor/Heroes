@props([
    'title',
    'description' => null,
    'name' => null,
    'value' => null,
    'checked' => false,
    'selected' => false,
    'tag' => 'label',
    'cardClass' => '',
])

@php
    $id = $name && $value ? str($name.'-'.$value)->slug('-') : null;
    $isButton = $tag === 'button';
@endphp

@if ($isButton)
    <button
        type="button"
        {{ $attributes->class("ob-style-card group {$cardClass}") }}
        @if ($selected) data-selected="true" @endif
    >
        <span class="ob-style-card__content">
            <span class="ob-style-card__visual">
                {{ $slot }}
            </span>
            <span class="space-y-1 text-left">
                <span class="block text-base font-semibold text-stone-950">{{ $title }}</span>
                @if ($description)
                    <span class="block text-sm leading-6 text-stone-600">{{ $description }}</span>
                @endif
            </span>
        </span>
    </button>
@else
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

        <span class="ob-style-card group {{ $cardClass }} peer-checked:border-slate-950 peer-checked:shadow-[0_18px_40px_rgba(15,23,42,0.12)]">
            <span class="ob-style-card__content">
                <span class="ob-style-card__visual">
                    {{ $slot }}
                </span>
                <span class="space-y-1 text-left">
                    <span class="block text-base font-semibold text-stone-950">{{ $title }}</span>
                    @if ($description)
                        <span class="block text-sm leading-6 text-stone-600">{{ $description }}</span>
                    @endif
                </span>
            </span>
        </span>
    </label>
@endif
