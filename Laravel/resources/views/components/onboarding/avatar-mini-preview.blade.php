@props([
    'bodyKey' => null,
    'hairKey' => null,
    'outfitPresetKey' => null,
    'equippedItems' => [],
    'skin' => null,
    'hairColor' => '#8C5A43',
    'accent' => '#4C6FFF',
    'hairScale' => null,
    'hairOffsetY' => null,
    'bodyVariantFile' => null,
    'previewRow' => null,
    'previewCol' => null,
])

@inject('avatarRenderer', 'App\Support\AvatarSvgRenderer')
@inject('avatarAssets', 'App\Support\AvatarAssetCatalog')

@php
    $bodyConfig = $bodyKey ? ($avatarAssets->bodyAsset($bodyKey) ?? []) : [];
    $hairConfig = $hairKey ? ($avatarAssets->hairAsset($hairKey) ?? []) : [];
    $resolvedEquipment = array_merge(is_array($equippedItems) ? $equippedItems : [], [
        'body' => $bodyKey,
    ]);
    $resolvedSkin = $skin ?? data_get($bodyConfig, 'skin', config('avatar.defaults.skin'));
    $resolvedHairColor = $hairColor ?? data_get($hairConfig, 'color', config('avatar.defaults.hair_color'));
    $resolvedHairScale = $hairScale ?? data_get($bodyConfig, 'hair_scale', '1.00');
    $resolvedHairOffsetY = $hairOffsetY ?? data_get($bodyConfig, 'hair_offset_y', '0%');
    $styleVariables = array_merge(
        $avatarRenderer->skinPalette($resolvedSkin),
        $avatarRenderer->hairPalette($resolvedHairColor),
        $avatarRenderer->outfitPalette($accent),
        [
            '--avatar-hair-scale' => $resolvedHairScale,
            '--avatar-hair-offset-y' => $resolvedHairOffsetY,
        ],
    );
    $styleString = collect($styleVariables)->map(fn (string $value, string $key): string => "{$key}: {$value}")->implode('; ');
    $usePreviewFrame = $previewRow !== null || $previewCol !== null;
    $resolvedPreviewRow = (int) ($previewRow ?? data_get($bodyConfig, 'preview.row', 0));
    $resolvedPreviewCol = (int) ($previewCol ?? data_get($bodyConfig, 'preview.col', 0));
    $bodyHtml = $bodyKey
        ? ($bodyVariantFile
            ? ($usePreviewFrame
                ? $avatarRenderer->renderBodyVariantByFileAtFrame($bodyVariantFile, $bodyConfig, $resolvedPreviewCol, $resolvedPreviewRow)->toHtml()
                : $avatarRenderer->renderBodyVariantByFile($bodyVariantFile, $bodyConfig)->toHtml())
            : ($usePreviewFrame
                ? $avatarRenderer->renderBodyAtFrame($bodyKey, $resolvedPreviewCol, $resolvedPreviewRow, 'lazy')->toHtml()
                : $avatarRenderer->renderBody($bodyKey, 'lazy')->toHtml()))
        : '';
    $outfitBaseHtml = $usePreviewFrame
        ? $avatarRenderer->renderOutfitPresetBaseAtFrame($outfitPresetKey, $resolvedEquipment, $resolvedPreviewCol, $resolvedPreviewRow, 'lazy')->toHtml()
        : $avatarRenderer->renderOutfitPresetBase($outfitPresetKey, $resolvedEquipment, 'lazy')->toHtml();
    $hairHtml = $hairKey
        ? ($usePreviewFrame
            ? $avatarRenderer->renderHairAtFrame($hairKey, $resolvedPreviewCol, $resolvedPreviewRow, 'lazy')->toHtml()
            : $avatarRenderer->renderHair($hairKey, 'lazy')->toHtml())
        : '';
    $outfitOverlayHtml = $usePreviewFrame
        ? $avatarRenderer->renderOutfitPresetOverlayAtFrame($outfitPresetKey, $resolvedEquipment, $resolvedPreviewCol, $resolvedPreviewRow, 'lazy')->toHtml()
        : $avatarRenderer->renderOutfitPresetOverlay($outfitPresetKey, $resolvedEquipment, 'lazy')->toHtml();
@endphp

<div
    {{ $attributes->class('ob-avatar-mini-preview')->merge(['style' => $styleString]) }}
>
    <div class="ob-avatar-choice-preview__layer">
        {!! $bodyHtml !!}
        {!! $outfitBaseHtml !!}
        {!! $hairHtml !!}
        {!! $outfitOverlayHtml !!}
    </div>
</div>
