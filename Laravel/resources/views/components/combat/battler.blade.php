@props([
    'battler',
])

<div
    class="{{ $battler['classes'] }}"
    data-actor-key="{{ $battler['key'] }}"
    style="{{ $battler['style'] }}"
>
    @if ($battler['is_current'])
        <div class="combat-active-indicator">▼ TOUR</div>
    @endif

    <div class="combat-battler-ground-ring"></div>
    <div class="combat-battler-shadow"></div>

    <div class="combat-sprite-shell">
        @if (($battler['sprite']['type'] ?? null) === 'sheet')
            <x-combat.sprite-sheet
                class="{{ $battler['sprite']['class'] }}"
                :payload="$battler['sprite']['payload']"
            />
        @elseif (($battler['sprite']['type'] ?? null) === 'image')
            <img
                src="{{ $battler['sprite']['src'] }}"
                alt="{{ $battler['sprite']['alt'] }}"
                style="{{ $battler['sprite']['style'] }}"
            >
        @else
            <div class="{{ $battler['sprite']['class'] }}">{{ $battler['sprite']['label'] }}</div>
        @endif
    </div>
</div>
