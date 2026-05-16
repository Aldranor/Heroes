@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-stone-300 bg-white text-stone-900 hover:-translate-y-0.5 hover:border-stone-500 hover:bg-stone-50',
        default => 'bg-stone-950 text-white shadow-[0_18px_32px_rgba(15,23,42,0.16)] hover:-translate-y-0.5 hover:bg-stone-800 hover:shadow-[0_22px_38px_rgba(15,23,42,0.18)]',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex w-full items-center justify-center rounded-[1.2rem] px-5 py-4 text-base font-semibold transition duration-200 disabled:cursor-not-allowed disabled:opacity-45 sm:w-auto',
        $classes,
    ]) }}
>
    {{ $slot }}
</button>
