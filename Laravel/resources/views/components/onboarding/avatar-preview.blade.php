@props([
    'defaultBodyKey',
    'defaultHairKey',
    'defaultOutfitPresetKey',
    'defaultSkin',
    'defaultHairColor',
    'defaultAccent',
    'defaultHairScale' => '1.00',
    'defaultHairOffsetY' => '0%',
    'defaultNickname' => null,
    'defaultClass' => null,
])

@inject('avatarRenderer', 'App\Support\AvatarSvgRenderer')
@inject('avatarAssets', 'App\Support\AvatarAssetCatalog')

@php
    $avatarStyleVars = collect(array_merge(
        $avatarRenderer->skinPalette($defaultSkin),
        $avatarRenderer->hairPalette($defaultHairColor),
        $avatarRenderer->outfitPalette($defaultAccent),
        [
            '--avatar-hair-scale' => $defaultHairScale,
            '--avatar-hair-offset-y' => $defaultHairOffsetY,
        ],
    ))->map(fn (string $value, string $key): string => "{$key}: {$value}")
        ->implode('; ');
@endphp

<section class="ob-preview-card rounded-[2rem] border border-white/10 bg-stone-950/90 p-5 shadow-[0_24px_48px_rgba(0,0,0,0.22)]">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="ob-kicker">{{ __('onboarding.preview.eyebrow') }}</p>
            <h2 class="rs-display mt-2 text-2xl text-stone-50">{{ __('onboarding.preview.title') }}</h2>
            <p class="mt-2 max-w-xs text-sm leading-6 text-stone-400">{{ __('onboarding.preview.hint') }}</p>
        </div>
        <span class="rounded-full bg-white/6 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-300">
            {{ __('onboarding.step_label') }}
        </span>
    </div>

    <div class="mt-5 flex flex-col items-center gap-4 text-center">
        <div class="ob-avatar-stage" data-avatar-stage style="{{ $avatarStyleVars }}">
            <div class="ob-avatar-frame">
                <div class="ob-avatar-layer" data-avatar-layer style="{{ $avatarStyleVars }}">
                    {!! $avatarRenderer->renderBody($defaultBodyKey) !!}
                    {!! $avatarRenderer->renderOutfitPresetBase($defaultOutfitPresetKey, ['body' => $defaultBodyKey]) !!}
                    {!! $avatarRenderer->renderHair($defaultHairKey) !!}
                    {!! $avatarRenderer->renderOutfitPresetOverlay($defaultOutfitPresetKey, ['body' => $defaultBodyKey]) !!}
                </div>
            </div>

                <div class="hidden" aria-hidden="true">
                    @foreach ($avatarAssets->bodyAssets() as $key => $asset)
                        <template data-avatar-template="body" data-avatar-key="{{ $key }}">
                            {!! $avatarRenderer->renderBody($key) !!}
                        </template>
                    @endforeach

                    @foreach ($avatarAssets->bodyVariants() as $key => $variant)
                        <template data-avatar-template="body-variant" data-avatar-key="{{ $key }}">
                            {!! $avatarRenderer->renderBodyVariant($key) !!}
                        </template>
                    @endforeach

                    @foreach ($avatarAssets->outfitPresets() as $key => $preset)
                    @foreach ($avatarAssets->bodyAssets() as $bodyKey => $bodyAsset)
                        <template data-avatar-template="outfit-preset-base" data-avatar-key="{{ $key }}::{{ $bodyKey }}">
                            {!! $avatarRenderer->renderOutfitPresetBase($key, ['body' => $bodyKey]) !!}
                        </template>
                        <template data-avatar-template="outfit-preset-overlay" data-avatar-key="{{ $key }}::{{ $bodyKey }}">
                            {!! $avatarRenderer->renderOutfitPresetOverlay($key, ['body' => $bodyKey]) !!}
                        </template>
                    @endforeach
                @endforeach
            </div>

        </div>

        <div class="space-y-1">
            <p class="rs-display text-3xl text-stone-50" data-avatar-nickname>{{ $defaultNickname }}</p>
            <p class="text-sm font-medium text-stone-300" data-avatar-class>{{ $defaultClass }}</p>
        </div>
    </div>
</section>
