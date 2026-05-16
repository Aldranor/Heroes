@props([
    'status' => 'not_started',
    'label' => null,
])

@php
    $tones = [
        'not_started' => 'border-white/10 bg-white/5 text-stone-300',
        'in_progress' => 'border-amber-200/25 bg-amber-300/12 text-amber-50',
        'mastered' => 'border-emerald-200/25 bg-emerald-300/14 text-emerald-50',
        'locked' => 'border-rose-200/25 bg-rose-300/12 text-rose-50',
    ];
@endphp

<span {{ $attributes->class(['inline-flex rounded-full border px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em]', $tones[$status] ?? $tones['not_started']]) }}>
    {{ $label ?? __("lessons.library.status.{$status}") }}
</span>
