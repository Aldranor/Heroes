@props([
    'href',
    'variant' => 'primary',
])

@php
    $classes = [
        'primary' => 'bg-amber-200 text-stone-950 hover:bg-amber-100',
        'secondary' => 'border border-white/10 bg-white/5 text-stone-100 hover:border-amber-200/30 hover:text-amber-50',
        'muted' => 'border border-white/8 text-stone-400 hover:border-white/20 hover:text-stone-100',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->class(['inline-flex justify-center rounded-full px-3 py-2 text-xs font-bold uppercase tracking-[0.14em] transition', $classes[$variant] ?? $classes['secondary']]) }}>
    {{ $slot }}
</a>
