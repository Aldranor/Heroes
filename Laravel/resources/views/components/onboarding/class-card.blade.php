@props([
    'name',
    'value',
    'title',
    'description',
    'identity' => null,
    'icon',
    'outfitPresetKey',
    'outfitAccent',
    'bodyKey',
    'hairKey',
    'skin' => null,
    'hairColor' => '#8C5A43',
    'hairScale' => '1.00',
    'hairOffsetY' => '0%',
    'checked' => false,
])

@php
    $id = 'class-'.str($value)->slug('-');
@endphp

<label for="{{ $id }}" class="block cursor-pointer">
    <input
        id="{{ $id }}"
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        class="peer sr-only"
        data-class-title="{{ $title }}"
        data-class-icon="{{ $icon }}"
        data-avatar-outfit-preset-key="{{ $outfitPresetKey }}"
        data-avatar-accent="{{ $outfitAccent }}"
        data-avatar-preview-body-key="{{ $bodyKey }}"
        data-avatar-preview-hair-key="{{ $hairKey }}"
        data-avatar-preview-skin="{{ $skin }}"
        data-avatar-preview-hair-color="{{ $hairColor }}"
        data-avatar-preview-hair-scale="{{ $hairScale }}"
        data-avatar-preview-hair-offset-y="{{ $hairOffsetY }}"
        @checked($checked)
    >

    <span class="ob-class-card" style="--ob-class-accent: {{ $outfitAccent }}">
        <span class="ob-class-card__visual">
            <x-onboarding.avatar-mini-preview
                class="ob-class-preview"
                data-class-avatar-preview
                data-class-outfit-preset-key="{{ $outfitPresetKey }}"
                data-class-accent="{{ $outfitAccent }}"
                :body-key="$bodyKey"
                :hair-key="$hairKey"
                :outfit-preset-key="$outfitPresetKey"
                :skin="$skin"
                :hair-color="$hairColor"
                :accent="$outfitAccent"
                :hair-scale="$hairScale"
                :hair-offset-y="$hairOffsetY"
            />
        </span>

        <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-3">
                <span class="space-y-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-stone-700 shadow-sm">
                        <span>{{ $icon }}</span>
                        <span>{{ __('onboarding.stepper.class') }}</span>
                    </span>
                    <span class="block rs-display text-[2rem] leading-none text-stone-950">{{ $title }}</span>
                </span>
                <span class="ob-choice-indicator shrink-0"></span>
            </span>

            <span class="mt-3 block text-sm font-semibold text-stone-900">{{ $description }}</span>

            @if ($identity)
                <span class="mt-2 block text-sm leading-6 text-stone-600">{{ $identity }}</span>
            @endif
        </span>
    </span>
</label>
