@props([
    'payload' => [],
    'animated' => true,
])

@php
    $craftpix = $payload['craftpix'] ?? null;
    $craftpixStates = is_array($craftpix['states'] ?? null) ? $craftpix['states'] : [];
    $craftpixDefault = (string) ($craftpix['default_state'] ?? 'idle');
@endphp

<div
    {{ $attributes->class(['combat-sprite-sheet'])->merge(array_filter([
        'data-sprite-sheet' => $animated ? 'true' : null,
        'data-sprite-animation-state' => $animated ? ($payload['animation_state'] ?? 'idle') : null,
        'data-sprite-cols' => $animated ? ($payload['columns'] ?? 1) : null,
        'data-base-col' => $animated ? ($payload['base_col'] ?? 0) : null,
        'data-animation-map' => $animated ? ($payload['animation_map_json'] ?? null) : null,
        'data-craftpix' => $craftpixStates !== [] ? json_encode($craftpixStates, JSON_THROW_ON_ERROR) : null,
        'data-craftpix-active' => $craftpixStates !== [] ? $craftpixDefault : null,
        'style' => $payload['style'] ?? '',
    ], fn ($value) => $value !== null)) }}
>
    <div class="combat-sprite-sheet__layers">
        @if ($craftpixStates !== [])
            {{-- Craftpix monsters: one <img> per animation state, only the
                 active one is visible (display:block via [data-active]).
                 The JS animator toggles `data-active` and updates the
                 parent's --combat-sprite-row/col CSS vars based on the
                 current frame within that state's grid. --}}
            @foreach ($craftpixStates as $stateKey => $stateMeta)
                <img
                    class="combat-sprite-sheet__image combat-sprite-sheet__image--craftpix"
                    src="{{ $stateMeta['url'] }}"
                    alt=""
                    aria-hidden="true"
                    draggable="false"
                    decoding="async"
                    data-state="{{ $stateKey }}"
                    @if ($stateKey === $craftpixDefault) data-active="true" @endif
                    style="--combat-sprite-cols: {{ (int) ($stateMeta['cols'] ?? 1) }}; --combat-sprite-rows: {{ (int) ($stateMeta['rows'] ?? 1) }};"
                >
            @endforeach
        @else
            @foreach (($payload['layers'] ?? []) as $layer)
                <img
                    class="combat-sprite-sheet__image"
                    src="{{ $layer['src'] }}"
                    alt=""
                    aria-hidden="true"
                    draggable="false"
                    loading="lazy"
                    decoding="async"
                    style="{{ $layer['style'] }}"
                >
            @endforeach
        @endif
    </div>
</div>
