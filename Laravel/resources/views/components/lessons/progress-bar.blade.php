@props([
    'value' => 0,
    'height' => 'h-2',
])

@php
    $progressValue = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes->class(['overflow-hidden rounded-full bg-white/8', $height]) }}>
    <div class="h-full rounded-full bg-amber-200 transition-all duration-500" style="width: {{ $progressValue }}%"></div>
</div>
