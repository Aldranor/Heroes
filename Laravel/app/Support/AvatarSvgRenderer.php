<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class AvatarSvgRenderer
{
    private array $sheetPayloadCache = [];

    public function __construct(
        private readonly AvatarAssetCatalog $assets,
        private readonly AvatarAssetLocator $locator,
    ) {}

    private const BODY_REPLACEMENTS = [
        '#DBA07A' => 'var(--avatar-skin-base)',
        '#EFC090' => 'var(--avatar-skin-light)',
        '#B07050' => 'var(--avatar-skin-dark)',
        '#7A4020' => 'var(--avatar-skin-deep)',
        '#9A5030' => 'var(--avatar-skin-shadow)',
        '#2B1714' => 'var(--avatar-outline, #2B1714)',
        '#2b1714' => 'var(--avatar-outline, #2B1714)',
        '#1A0E08' => 'var(--avatar-eye, #171717)',
        '#FFFFFF' => 'var(--avatar-eye-light, #FFFFFF)',
    ];

    private const HAIR_REPLACEMENTS = [
        '#2A1A16' => 'var(--avatar-hair-deep)',
        '#4A2A1D' => 'var(--avatar-hair-dark)',
        '#7A4327' => 'var(--avatar-hair-base)',
        '#A8643C' => 'var(--avatar-hair-light)',
        '#1D4ED8' => 'var(--avatar-hair-detail, var(--avatar-outfit-detail))',
    ];

    private const OUTFIT_REPLACEMENTS = [
        '#4C6FFF' => 'var(--avatar-outfit-base)',
        '#6F8FFF' => 'var(--avatar-outfit-light)',
        '#3FA34D' => 'var(--avatar-outfit-base)',
        '#5FCF6A' => 'var(--avatar-outfit-light)',
        '#FF8C42' => 'var(--avatar-outfit-base)',
        '#FFAA6B' => 'var(--avatar-outfit-light)',
        '#7B5EA7' => 'var(--avatar-outfit-base)',
        '#9B7ED7' => 'var(--avatar-outfit-light)',
        '#3A6B8C' => 'var(--avatar-outfit-base)',
        '#5A8CAD' => 'var(--avatar-outfit-light)',
        '#6B4F3A' => 'var(--avatar-outfit-base)',
        '#8C6A4F' => 'var(--avatar-outfit-light)',
        '#2B1714' => 'var(--avatar-outfit-deep)',
        '#2b1714' => 'var(--avatar-outfit-deep)',
        '#2F4F4F' => 'var(--avatar-outfit-dark)',
        '#2f4f4f' => 'var(--avatar-outfit-dark)',
        '#5F9EA0' => 'var(--avatar-outfit-base)',
        '#5f9ea0' => 'var(--avatar-outfit-base)',
        '#9FD3D3' => 'var(--avatar-outfit-light)',
        '#9fd3d3' => 'var(--avatar-outfit-light)',
        '#6B3F2A' => 'var(--avatar-outfit-dark)',
        '#6b3f2a' => 'var(--avatar-outfit-dark)',
        '#9AA0A6' => 'var(--avatar-outfit-light)',
        '#9aa0a6' => 'var(--avatar-outfit-light)',
        '#D4AF37' => 'var(--avatar-outfit-detail)',
        '#d4af37' => 'var(--avatar-outfit-detail)',
    ];

    public function renderBody(string $key, string $loading = 'eager'): HtmlString
    {
        if ($this->assets->bodyAsset($key) === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $this->assets->bodyAsset($key),
            self::BODY_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--body',
            $loading,
        );
    }

    public function renderBodyAtFrame(string $key, int $column, int $row, string $loading = 'eager'): HtmlString
    {
        $asset = $this->assets->bodyAsset($key);

        if ($asset === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $this->overridePreviewFrame($this->resolveCombatSheetAsset($asset), $column, $row),
            self::BODY_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--body',
            $loading,
        );
    }

    public function renderBodyVariant(string $key, string $loading = 'eager'): HtmlString
    {
        $variant = $this->assets->bodyVariants()[$key] ?? null;

        if ($variant === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $variant,
            self::BODY_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--body',
            $loading,
        );
    }

    public function renderBodyVariantByFile(string $file, array $bodyConfig, string $loading = 'eager'): HtmlString
    {
        $preview = array_replace(
            config('avatar.preview'),
            config('avatar.body_defaults.preview', []),
            data_get($bodyConfig, 'preview', []),
        );

        $asset = [
            'file' => $file,
            'type' => 'sheet_png',
            'preview' => $preview,
        ];

        return $this->renderConfiguredAsset(
            $asset,
            self::BODY_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--body',
            $loading,
        );
    }

    public function renderBodyVariantByFileAtFrame(string $file, array $bodyConfig, int $column, int $row, string $loading = 'eager'): HtmlString
    {
        $preview = array_replace(
            config('avatar.preview'),
            config('avatar.body_defaults.preview', []),
            data_get($bodyConfig, 'preview', []),
            [
                'col' => $column,
                'row' => $row,
            ],
        );

        $asset = [
            'file' => $file,
            'type' => 'sheet_png',
            'preview' => $preview,
        ];

        return $this->renderConfiguredAsset(
            $asset,
            self::BODY_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--body',
            $loading,
        );
    }

    public function renderHair(string $key, string $loading = 'eager'): HtmlString
    {
        if ($this->assets->hairAsset($key) === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $this->assets->hairAsset($key),
            self::HAIR_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--hair',
            $loading,
        );
    }

    public function renderHairAtFrame(string $key, int $column, int $row, string $loading = 'eager'): HtmlString
    {
        $asset = $this->assets->hairAsset($key);

        if ($asset === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $this->overridePreviewFrame($asset, $column, $row),
            self::HAIR_REPLACEMENTS,
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--hair',
            $loading,
        );
    }

    public function renderOutfit(string $key, string $loading = 'eager'): HtmlString
    {
        return $this->renderWearable($key, 'full_outfit', $loading);
    }

    public function renderWearable(string $key, string $slot, string $loading = 'eager'): HtmlString
    {
        if ($this->assets->wearableAsset($key) === null) {
            return new HtmlString('');
        }

        return $this->renderConfiguredAsset(
            $this->assets->wearableAsset($key),
            self::OUTFIT_REPLACEMENTS,
            "ob-avatar-asset ob-avatar-svg ob-avatar-svg--outfit ob-avatar-svg--{$slot}",
            $loading,
        );
    }

    public function renderOutfitPreset(?string $presetKey, array $equippedItems = [], string $loading = 'eager'): HtmlString
    {
        return new HtmlString(
            $this->renderOutfitPresetBase($presetKey, $equippedItems, $loading)->toHtml()
            .$this->renderOutfitPresetOverlay($presetKey, $equippedItems, $loading)->toHtml()
        );
    }

    public function renderOutfitPresetBase(?string $presetKey, array $equippedItems = [], string $loading = 'eager'): HtmlString
    {
        $resolved = $this->assets->resolveOutfitPreset($presetKey, $equippedItems);

        return $this->renderResolvedWearableSlots($resolved['slots'] ?? [], [
            'full_outfit',
            'back',
            'pants',
            'shoes',
            'top',
        ], $loading);
    }

    public function renderOutfitPresetBaseAtFrame(?string $presetKey, array $equippedItems = [], int $column = 0, int $row = 0, string $loading = 'eager'): HtmlString
    {
        $resolved = $this->assets->resolveOutfitPreset($presetKey, $equippedItems);

        return $this->renderResolvedWearableSlotsAtFrame($resolved['slots'] ?? [], [
            'full_outfit',
            'back',
            'pants',
            'shoes',
            'top',
        ], $column, $row, $loading);
    }

    public function renderOutfitPresetOverlay(?string $presetKey, array $equippedItems = [], string $loading = 'eager'): HtmlString
    {
        $resolved = $this->assets->resolveOutfitPreset($presetKey, $equippedItems);

        return $this->renderResolvedWearableSlots($resolved['slots'] ?? [], [
            'facial_hair',
            'helmet',
            'accessory',
        ], $loading);
    }

    public function renderOutfitPresetOverlayAtFrame(?string $presetKey, array $equippedItems = [], int $column = 0, int $row = 0, string $loading = 'eager'): HtmlString
    {
        $resolved = $this->assets->resolveOutfitPreset($presetKey, $equippedItems);

        return $this->renderResolvedWearableSlotsAtFrame($resolved['slots'] ?? [], [
            'facial_hair',
            'helmet',
            'accessory',
        ], $column, $row, $loading);
    }

    private function renderConfiguredAsset(?array $asset, array $replacements, string $className, string $loading = 'eager'): HtmlString
    {
        if (! is_array($asset)) {
            throw new InvalidArgumentException('Avatar asset is not configured.');
        }

        if (($asset['type'] ?? 'svg') === 'sheet_png') {
            return $this->renderSheetAsset($asset, $className, $loading);
        }

        return $this->renderSvgAsset(
            $asset['file'] ?? null,
            $replacements,
            $className,
        );
    }

    public function renderSvgAsset(?string $file, array $replacements, string $className): HtmlString
    {
        if (! is_string($file) || $file === '') {
            throw new InvalidArgumentException('Avatar asset file is not configured.');
        }

        $path = $this->locator->absolutePath($file);

        if (! is_string($path) || ! File::exists($path)) {
            throw new InvalidArgumentException("Avatar asset [{$file}] is missing.");
        }

        $svg = str_ireplace(array_keys($replacements), array_values($replacements), File::get($path));

        if (! str_contains($svg, 'xmlns=')) {
            $svg = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1) ?? $svg;
        }

        if (preg_match('/<svg\b[^>]*class="/i', $svg) === 1) {
            $svg = preg_replace('/class="([^"]*)"/i', 'class="$1 '.$className.'"', $svg, 1) ?? $svg;
        } else {
            $svg = preg_replace('/<svg\b/i', '<svg class="'.$className.'"', $svg, 1) ?? $svg;
        }

        if (! str_contains($svg, 'aria-hidden=')) {
            $svg = preg_replace('/<svg\b([^>]*)>/i', '<svg$1 aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMid meet">', $svg, 1) ?? $svg;
        }

        return new HtmlString($svg);
    }

    public function sheetAssetPayload(?array $asset): ?array
    {
        if (! is_array($asset) || (($asset['type'] ?? null) !== 'sheet_png')) {
            return null;
        }

        $cacheKey = md5(json_encode([
            $asset['file'] ?? null,
            data_get($asset, 'preview.frame_width'),
            data_get($asset, 'preview.frame_height'),
            data_get($asset, 'preview.col'),
            data_get($asset, 'preview.row'),
        ]));

        if (isset($this->sheetPayloadCache[$cacheKey])) {
            return $this->sheetPayloadCache[$cacheKey];
        }

        $file = $asset['file'] ?? null;

        if (! is_string($file) || $file === '') {
            throw new InvalidArgumentException('Avatar sheet asset file is not configured.');
        }

        $path = $this->locator->absolutePath($file);

        if (! is_string($path) || ! File::exists($path)) {
            throw new InvalidArgumentException("Avatar asset [{$file}] is missing.");
        }

        $imageSize = getimagesize($path);

        if ($imageSize === false) {
            throw new InvalidArgumentException("Avatar asset [{$file}] could not be read.");
        }

        $frameWidth = (int) data_get($asset, 'preview.frame_width', 80);
        $frameHeight = (int) data_get($asset, 'preview.frame_height', 64);
        $column = (int) data_get($asset, 'preview.col', 0);
        $row = (int) data_get($asset, 'preview.row', 0);
        $frameX = $column * $frameWidth;
        $frameY = $row * $frameHeight;
        $sheetWidthPercent = ($imageSize[0] / $frameWidth) * 100;
        $sheetHeightPercent = ($imageSize[1] / $frameHeight) * 100;
        $leftPercent = -($frameX / $frameWidth) * 100;
        $topPercent = -($frameY / $frameHeight) * 100;

        return $this->sheetPayloadCache[$cacheKey] = [
            'src' => $this->locator->url($file),
            'image_style' => sprintf(
                'width: %.4F%%; height: %.4F%%; left: %.4F%%; top: %.4F%%;',
                $sheetWidthPercent,
                $sheetHeightPercent,
                $leftPercent,
                $topPercent,
            ),
        ];
    }

    public function sheetAssetSrc(?array $asset): ?string
    {
        if (! is_array($asset) || (($asset['type'] ?? null) !== 'sheet_png')) {
            return null;
        }

        $file = $asset['file'] ?? null;

        if (! is_string($file) || $file === '') {
            return null;
        }

        return $this->locator->url($file);
    }

    public function combatSheetMetadata(?array $asset, ?int $row = null): ?array
    {
        if (! is_array($asset) || (($asset['type'] ?? null) !== 'sheet_png')) {
            return null;
        }

        $asset = $this->resolveCombatSheetAsset($asset);
        $file = $asset['file'] ?? null;

        if (! is_string($file) || $file === '') {
            return null;
        }

        $path = $this->locator->absolutePath($file);

        if (! is_string($path) || ! File::exists($path)) {
            return null;
        }

        $imageSize = getimagesize($path);

        if ($imageSize === false) {
            return null;
        }

        $frameWidth = max(1, (int) data_get($asset, 'preview.frame_width', config('avatar.preview.frame_width', 64)));
        $frameHeight = max(1, (int) data_get($asset, 'preview.frame_height', config('avatar.preview.frame_height', 64)));
        $columns = max(1, (int) floor($imageSize[0] / $frameWidth));
        $rows = max(1, (int) floor($imageSize[1] / $frameHeight));
        $resolvedRow = is_int($row) ? $row : (int) data_get($asset, 'preview.row', 0);

        return [
            'src' => $this->locator->url($file),
            'columns' => $columns,
            'rows' => $rows,
            'row' => min(max(0, $resolvedRow), $rows - 1),
            'frame_width' => $frameWidth,
            'frame_height' => $frameHeight,
        ];
    }

    private function renderSheetAsset(array $asset, string $className, string $loading = 'eager'): HtmlString
    {
        $payload = $this->sheetAssetPayload($asset);

        return new HtmlString(sprintf(
            '<div class="%s" aria-hidden="true"><img class="ob-avatar-sprite__image" src="%s" alt="" style="%s" loading="%s" decoding="async" fetchpriority="%s" draggable="false"></div>',
            e($className),
            e($payload['src'] ?? ''),
            e($payload['image_style'] ?? ''),
            e($loading),
            e($loading === 'eager' ? 'high' : 'low'),
        ));
    }

    public function skinPalette(string $hex): array
    {
        $mixColor = static function (string $source, string $target, float $ratio): string {
            [$sourceRed, $sourceGreen, $sourceBlue] = sscanf(ltrim($source, '#'), '%02x%02x%02x');
            [$targetRed, $targetGreen, $targetBlue] = sscanf(ltrim($target, '#'), '%02x%02x%02x');

            return sprintf(
                '#%02X%02X%02X',
                (int) round($sourceRed + (($targetRed - $sourceRed) * $ratio)),
                (int) round($sourceGreen + (($targetGreen - $sourceGreen) * $ratio)),
                (int) round($sourceBlue + (($targetBlue - $sourceBlue) * $ratio)),
            );
        };

        return [
            '--avatar-skin-base' => $hex,
            '--avatar-skin-light' => $mixColor($hex, '#FFE7D3', 0.34),
            '--avatar-skin-dark' => $mixColor($hex, '#8C5437', 0.28),
            '--avatar-skin-deep' => $mixColor($hex, '#532B1C', 0.48),
            '--avatar-skin-shadow' => $mixColor($hex, '#6E3926', 0.38),
            '--avatar-outline' => $mixColor($hex, '#6E3926', 0.38),
            '--avatar-eye' => '#171717',
            '--avatar-eye-light' => '#FFFFFF',
        ];
    }

    public function hairPalette(string $hex): array
    {
        $mixColor = static function (string $source, string $target, float $ratio): string {
            [$sourceRed, $sourceGreen, $sourceBlue] = sscanf(ltrim($source, '#'), '%02x%02x%02x');
            [$targetRed, $targetGreen, $targetBlue] = sscanf(ltrim($target, '#'), '%02x%02x%02x');

            return sprintf(
                '#%02X%02X%02X',
                (int) round($sourceRed + (($targetRed - $sourceRed) * $ratio)),
                (int) round($sourceGreen + (($targetGreen - $sourceGreen) * $ratio)),
                (int) round($sourceBlue + (($targetBlue - $sourceBlue) * $ratio)),
            );
        };

        return [
            '--avatar-hair-base' => $hex,
            '--avatar-hair-light' => $mixColor($hex, '#EBC99C', 0.20),
            '--avatar-hair-dark' => $mixColor($hex, '#42241A', 0.32),
            '--avatar-hair-deep' => $mixColor($hex, '#21110C', 0.54),
            '--avatar-hair-detail' => $mixColor($hex, '#F6E7C0', 0.55),
        ];
    }

    public function outfitPalette(string $accent): array
    {
        $mixColor = static function (string $source, string $target, float $ratio): string {
            [$sourceRed, $sourceGreen, $sourceBlue] = sscanf(ltrim($source, '#'), '%02x%02x%02x');
            [$targetRed, $targetGreen, $targetBlue] = sscanf(ltrim($target, '#'), '%02x%02x%02x');

            return sprintf(
                '#%02X%02X%02X',
                (int) round($sourceRed + (($targetRed - $sourceRed) * $ratio)),
                (int) round($sourceGreen + (($targetGreen - $sourceGreen) * $ratio)),
                (int) round($sourceBlue + (($targetBlue - $sourceBlue) * $ratio)),
            );
        };

        return [
            '--avatar-outfit-base' => $accent,
            '--avatar-outfit-light' => $mixColor($accent, '#F6E7C0', 0.26),
            '--avatar-outfit-dark' => $mixColor($accent, '#1F2937', 0.34),
            '--avatar-outfit-deep' => $mixColor($accent, '#111827', 0.52),
            '--avatar-outfit-detail' => $mixColor($accent, '#FFF4D4', 0.68),
            '--avatar-stage-aura' => $accent,
        ];
    }

    private function renderResolvedWearableSlots(array $slots, array $order, string $loading = 'eager'): HtmlString
    {
        $html = '';

        foreach ($order as $slot) {
            $assetKey = $slots[$slot] ?? null;

            if (! is_string($assetKey) || $assetKey === '') {
                continue;
            }

            $html .= $this->renderWearable($assetKey, $slot, $loading)->toHtml();
        }

        return new HtmlString($html);
    }

    private function renderResolvedWearableSlotsAtFrame(array $slots, array $order, int $column, int $row, string $loading = 'eager'): HtmlString
    {
        $html = '';

        foreach ($order as $slot) {
            $assetKey = $slots[$slot] ?? null;

            if (! is_string($assetKey) || $assetKey === '') {
                continue;
            }

            $asset = $this->assets->wearableAsset($assetKey);

            if ($asset === null) {
                continue;
            }

            $html .= $this->renderConfiguredAsset(
                $this->overridePreviewFrame($asset, $column, $row),
                self::OUTFIT_REPLACEMENTS,
                "ob-avatar-asset ob-avatar-svg ob-avatar-svg--outfit ob-avatar-svg--{$slot}",
                $loading,
            )->toHtml();
        }

        return new HtmlString($html);
    }

    private function overridePreviewFrame(array $asset, int $column, int $row): array
    {
        $asset['preview'] = array_replace(
            config('avatar.preview'),
            data_get($asset, 'preview', []),
            [
                'col' => $column,
                'row' => $row,
            ],
        );

        return $asset;
    }

    private function resolveCombatSheetAsset(array $asset): array
    {
        $file = $asset['file'] ?? null;

        if (! is_string($file) || $file === '') {
            return $asset;
        }

        if ($this->spriteSheetRows($file) > \App\Support\LpcSprite::UNIVERSAL_ROW_COUNT) {
            return $asset;
        }

        $extendedVariant = $this->findExtendedVariantForCombat($file);
        if (is_string($extendedVariant)) {
            $asset['file'] = $extendedVariant;

            return $asset;
        }

        $directory = str_replace('\\', '/', pathinfo($file, PATHINFO_DIRNAME));
        $universalFile = trim($directory.'/Universal.png', '/');

        if ($this->locator->exists($universalFile)) {
            $asset['file'] = $universalFile;
        }

        return $asset;
    }

    private function findExtendedVariantForCombat(string $file): ?string
    {
        $directory = trim(str_replace('\\', '/', pathinfo($file, PATHINFO_DIRNAME)), '/');

        if ($directory === '' || str_contains($directory, '/Variants')) {
            return null;
        }

        $variantsDirectory = trim($directory.'/Variants', '/');
        $absoluteVariantsDirectory = $this->locator->directoryPath($variantsDirectory);

        if ($absoluteVariantsDirectory === null || ! File::isDirectory($absoluteVariantsDirectory)) {
            return null;
        }

        $files = array_values(array_filter(
            File::files($absoluteVariantsDirectory),
            fn ($candidate): bool => strtolower($candidate->getExtension()) === 'png',
        ));

        usort($files, fn ($left, $right): int => strnatcasecmp($left->getFilename(), $right->getFilename()));

        foreach ($files as $candidate) {
            $relativePath = trim($variantsDirectory.'/'.$candidate->getFilename(), '/');

            if ($this->spriteSheetRows($relativePath) > \App\Support\LpcSprite::UNIVERSAL_ROW_COUNT) {
                return $relativePath;
            }
        }

        return null;
    }

    private function spriteSheetRows(string $file): int
    {
        $absolutePath = $this->locator->absolutePath($file);

        if (! is_string($absolutePath) || ! File::exists($absolutePath)) {
            return 0;
        }

        $imageSize = @getimagesize($absolutePath);
        if (! is_array($imageSize) || ($imageSize[1] ?? 0) <= 0) {
            return 0;
        }

        return max(1, (int) floor($imageSize[1] / 64));
    }
}
