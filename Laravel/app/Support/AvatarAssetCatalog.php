<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AvatarAssetCatalog
{
    private ?array $bodyAssets = null;

    private ?array $bodyVariants = null;

    private ?array $wearableAssets = null;

    private ?array $hairAssets = null;

    private ?array $outfitAssets = null;

    private ?array $outfitPresets = null;

    public function __construct(
        private readonly AvatarAssetLocator $locator,
    ) {}

    public function bodyAssets(): array
    {
        if ($this->bodyAssets !== null) {
            return $this->bodyAssets;
        }

        return $this->bodyAssets = $this->locator->directoryPath(config('avatar.asset_directories.body', 'bodies')) !== null
            ? $this->discoverLpcBodyAssets()
            : $this->discoverFlatAssets('body');
    }

    public function bodyAsset(string $key): ?array
    {
        return $this->bodyAssets()[$key] ?? null;
    }

    public function bodyProfileForKey(?string $key): ?string
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        return data_get($this->bodyAsset($key) ?? [], 'frame_profile');
    }

    public function bodyVariants(): array
    {
        if ($this->bodyVariants !== null) {
            return $this->bodyVariants;
        }

        if ($this->locator->directoryPath(config('avatar.asset_directories.body', 'bodies')) === null) {
            return $this->bodyVariants = [];
        }

        $variants = [];

        foreach (config('avatar.body_profiles', []) as $key => $profile) {
            $relativeDirectory = data_get($profile, 'path');

            if (! is_string($relativeDirectory) || $relativeDirectory === '') {
                continue;
            }

            $variantPath = $this->locator->directoryPath("bodies/{$relativeDirectory}/Variants");

            if ($variantPath === null) {
                continue;
            }

            $files = array_values(array_filter(
                File::files($variantPath),
                fn ($file): bool => strtolower($file->getExtension()) === 'png',
            ));

            usort($files, fn ($left, $right): int => strnatcasecmp($left->getFilename(), $right->getFilename()));

            $previewConfig = array_replace(
                config('avatar.preview'),
                config('avatar.body_defaults.preview', []),
                data_get($profile, 'preview', []),
            );

            foreach ($files as $index => $file) {
                $variantKey = Str::slug(pathinfo($file->getFilename(), PATHINFO_FILENAME));
                $assetKey = "{$key}--{$variantKey}";

                $variants[$assetKey] = [
                    'key' => $assetKey,
                    'label' => $this->colorLabel($variantKey),
                    'file' => "bodies/{$relativeDirectory}/Variants/".$file->getFilename(),
                    'type' => 'sheet_png',
                    'parent_key' => $key,
                    'variant_key' => $variantKey,
                    'variant_label' => $this->colorLabel($variantKey),
                    'frame_profile' => data_get($profile, 'frame_profile', 'female'),
                    'skin' => config('avatar.defaults.skin'),
                    'hair_scale' => data_get($profile, 'hair_scale', config('avatar.body_defaults.hair_scale', '1.00')),
                    'hair_offset_y' => data_get($profile, 'hair_offset_y', config('avatar.body_defaults.hair_offset_y', '0%')),
                    'sort_order' => data_get($profile, 'sort_order', PHP_INT_MAX) * 100 + $index,
                    'preview' => $previewConfig,
                ];
            }
        }

        uasort($variants, fn (array $left, array $right): int => ($left['sort_order'] ?? PHP_INT_MAX) <=> ($right['sort_order'] ?? PHP_INT_MAX));

        return $this->bodyVariants = $variants;
    }

    public function bodyVariantsForBody(string $bodyKey): array
    {
        $allVariants = $this->bodyVariants();

        return collect($allVariants)
            ->filter(fn (array $variant): bool => $variant['parent_key'] === $bodyKey)
            ->all();
    }

    public function hairAssets(): array
    {
        if ($this->hairAssets !== null) {
            return $this->hairAssets;
        }

        return $this->hairAssets = $this->locator->directoryPath(config('avatar.asset_directories.hair', 'hairs')) !== null
            ? $this->discoverLpcHairAssets()
            : $this->discoverFlatAssets('hair');
    }

    public function hairAsset(string $key): ?array
    {
        return $this->hairAssets()[$key] ?? null;
    }

    public function hairModels(): array
    {
        $models = [];

        foreach ($this->hairAssets() as $key => $asset) {
            $styleKey = $asset['model_key'] ?? null;
            $profile = $asset['frame_profile'] ?? null;

            if (! is_string($styleKey) || ! is_string($profile)) {
                continue;
            }

            if (! isset($models[$styleKey])) {
                $models[$styleKey] = [
                    'key' => $styleKey,
                    'label' => $asset['model_label'] ?? $this->humanizeSlug($styleKey),
                    'description' => $asset['model_description'] ?? config('avatar.hair_defaults.description'),
                    'sort_order' => $asset['model_sort_order'] ?? PHP_INT_MAX,
                    'profiles' => [],
                    'supported_profiles' => [],
                    'default_variant_key' => $key,
                ];
            }

            if (! isset($models[$styleKey]['profiles'][$profile])) {
                $models[$styleKey]['profiles'][$profile] = [
                    'profile' => $profile,
                    'default_variant_key' => $key,
                    'variants' => [],
                ];
            }

            $models[$styleKey]['profiles'][$profile]['variants'][$key] = $asset + ['key' => $key];
            $models[$styleKey]['supported_profiles'][$profile] = true;
        }

        $preferredVariant = Str::slug(config('avatar.hair_defaults.preferred_variant', 'black'));
        $defaultBodyProfile = $this->bodyProfileForKey(config('avatar.defaults.body')) ?? 'female';

        uasort($models, function (array $left, array $right): int {
            $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
            $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strnatcasecmp($left['label'] ?? '', $right['label'] ?? '');
        });

        foreach ($models as &$model) {
            foreach ($model['profiles'] as &$profileGroup) {
                uasort($profileGroup['variants'], function (array $left, array $right): int {
                    $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
                    $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

                    if ($leftOrder !== $rightOrder) {
                        return $leftOrder <=> $rightOrder;
                    }

                    return strnatcasecmp($left['variant_label'] ?? '', $right['variant_label'] ?? '');
                });

                $profileGroup['variants'] = array_values($profileGroup['variants']);
                $profileGroup['default_variant_key'] = $this->resolvePreferredVariantKey(
                    $profileGroup['variants'],
                    $preferredVariant,
                ) ?? $profileGroup['default_variant_key'];
            }

            $model['supported_profiles'] = array_keys($model['supported_profiles']);
            $defaultProfile = in_array($defaultBodyProfile, $model['supported_profiles'], true)
                ? $defaultBodyProfile
                : ($model['supported_profiles'][0] ?? null);
            $model['default_profile'] = $defaultProfile;
            $model['default_variant_key'] = $defaultProfile
                ? ($model['profiles'][$defaultProfile]['default_variant_key'] ?? $model['default_variant_key'])
                : $model['default_variant_key'];
        }

        return $models;
    }

    public function wearableAssets(): array
    {
        return $this->wearableAssets ??= $this->discoverWearableAssets();
    }

    public function wearableAsset(string $key): ?array
    {
        return $this->wearableAssets()[$key] ?? null;
    }

    public function outfitAssets(): array
    {
        return $this->outfitAssets ??= $this->wearableAssets();
    }

    public function outfitAsset(string $key): ?array
    {
        return $this->wearableAsset($key);
    }

    public function outfitPresets(): array
    {
        if ($this->outfitPresets !== null) {
            return $this->outfitPresets;
        }

        $presets = [];

        foreach (config('avatar.outfit_presets', []) as $key => $preset) {
            if (! is_array($preset)) {
                continue;
            }

            $presets[$key] = $this->normalizeOutfitPreset($key, $preset);
        }

        uasort($presets, function (array $left, array $right): int {
            $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
            $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strnatcasecmp($left['label'] ?? '', $right['label'] ?? '');
        });

        return $this->outfitPresets = $presets;
    }

    public function outfitPreset(string $key): ?array
    {
        return $this->outfitPresets()[$key] ?? null;
    }

    public function resolveOutfitPreset(?string $presetKey, array $equippedItems = []): array
    {
        $presetKey ??= data_get($equippedItems, 'outfit_preset');
        $presetKey ??= data_get($equippedItems, 'outfit');

        $preset = is_string($presetKey) ? $this->outfitPreset($presetKey) : null;
        $bodyProfile = $this->bodyProfileForKey(data_get($equippedItems, 'body')) ?? 'female';
        $presetSlots = data_get($preset, "profiles.{$bodyProfile}", data_get($preset, 'slots', $this->emptyWearableSlots()));
        $slots = is_array($presetSlots) ? $presetSlots : $this->emptyWearableSlots();
        $equipment = data_get($equippedItems, 'equipment', []);

        if (! is_array($equipment)) {
            $equipment = [];
        }

        foreach ($this->wearableSlots() as $slot) {
            if (! array_key_exists($slot, $equipment)) {
                continue;
            }

            $value = $equipment[$slot];

            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($slot === 'full_outfit') {
                $slots = $this->emptyWearableSlots();
                $slots['full_outfit'] = $value;

                continue;
            }

            if (($slots['full_outfit'] ?? null) !== null) {
                continue;
            }

            $slots[$slot] = $value;
        }

        return [
            'preset_key' => $presetKey,
            'label' => $preset['label'] ?? null,
            'slots' => $slots,
            'body_profile' => $bodyProfile,
        ];
    }

    private function discoverLpcBodyAssets(): array
    {
        $assets = [];

        foreach (config('avatar.body_profiles', []) as $key => $profile) {
            $relativeDirectory = data_get($profile, 'path');

            if (! is_string($relativeDirectory) || $relativeDirectory === '') {
                continue;
            }

            $preferredSheet = data_get($profile, 'preview_sheet', config('avatar.body_defaults.preview_sheet', 'Idle.png'));
            $candidateFiles = array_values(array_unique(array_filter([
                is_string($preferredSheet) ? "bodies/{$relativeDirectory}/{$preferredSheet}" : null,
                "bodies/{$relativeDirectory}/Universal.png",
            ])));
            $file = collect($candidateFiles)->first(fn (string $candidate): bool => $this->locator->exists($candidate));

            if (! is_string($file) || $file === '') {
                continue;
            }

            $assets[$key] = [
                'label' => $this->translatedText("onboarding.avatar.body_profiles.{$key}.label", data_get($profile, 'label', $this->humanizeSlug($key))),
                'description' => $this->translatedText("onboarding.avatar.body_profiles.{$key}.description", data_get($profile, 'description', config('avatar.body_defaults.description'))),
                'file' => $file,
                'type' => 'sheet_png',
                'skin' => config('avatar.defaults.skin'),
                'frame_profile' => data_get($profile, 'frame_profile', 'female'),
                'hair_scale' => data_get($profile, 'hair_scale', config('avatar.body_defaults.hair_scale', '1.00')),
                'hair_offset_y' => data_get($profile, 'hair_offset_y', config('avatar.body_defaults.hair_offset_y', '0%')),
                'sort_order' => data_get($profile, 'sort_order', PHP_INT_MAX),
                'preview' => array_replace(config('avatar.preview'), config('avatar.body_defaults.preview', []), data_get($profile, 'preview', [])),
            ];
        }

        uasort($assets, fn (array $left, array $right): int => ($left['sort_order'] ?? PHP_INT_MAX) <=> ($right['sort_order'] ?? PHP_INT_MAX));

        return $assets;
    }

    private function discoverLpcHairAssets(): array
    {
        $path = $this->locator->directoryPath(config('avatar.asset_directories.hair', 'hairs'));
        $assets = [];

        if ($path === null) {
            return $assets;
        }

        $styleDirectories = File::directories($path);
        usort($styleDirectories, fn (string $left, string $right): int => strnatcasecmp(basename($left), basename($right)));

        foreach ($styleDirectories as $styleDirectory) {
            $styleFolder = basename($styleDirectory);
            $styleKey = Str::slug($styleFolder);
            $configuredHairModels = config('avatar.hair_models', []);

            if ($configuredHairModels !== [] && ! array_key_exists($styleKey, $configuredHairModels)) {
                continue;
            }

            $styleLabel = $this->translatedText("onboarding.avatar.hair_styles.{$styleKey}.label", $this->humanizeSlug($styleKey));
            $styleDescription = $this->translatedText("onboarding.avatar.hair_styles.{$styleKey}.description", config('avatar.hair_defaults.description'));
            $styleSortOrder = data_get($configuredHairModels, "{$styleKey}.sort_order", PHP_INT_MAX);

            foreach (config('avatar.hair_defaults.supported_profiles', ['female', 'male']) as $profile) {
                $assets += $this->buildLpcHairAssetsForSource(
                    styleFolder: $styleFolder,
                    styleKey: $styleKey,
                    sourceProfile: $profile,
                    targetProfiles: [$profile],
                    styleLabel: $styleLabel,
                    styleDescription: $styleDescription,
                    styleSortOrder: $styleSortOrder,
                    supportsVariants: true,
                );
            }

            foreach (config('avatar.hair_defaults.generic_sources', []) as $sourceProfile => $targetProfiles) {
                if (! is_array($targetProfiles) || $targetProfiles === []) {
                    continue;
                }

                $assets += $this->buildLpcHairAssetsForSource(
                    styleFolder: $styleFolder,
                    styleKey: $styleKey,
                    sourceProfile: (string) $sourceProfile,
                    targetProfiles: array_values(array_filter($targetProfiles, 'is_string')),
                    styleLabel: $styleLabel,
                    styleDescription: $styleDescription,
                    styleSortOrder: $styleSortOrder,
                    supportsVariants: false,
                );
            }
        }

        uasort($assets, function (array $left, array $right): int {
            $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
            $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strnatcasecmp($left['label'] ?? '', $right['label'] ?? '');
        });

        return $assets;
    }

    private function discoverFlatAssets(string $group): array
    {
        $directory = config("avatar.asset_directories.{$group}", Str::plural($group));
        $path = $this->locator->directoryPath($directory);
        $generated = [];

        if ($path !== null) {
            $files = File::files($path);
            usort($files, fn ($left, $right): int => strnatcasecmp($left->getFilename(), $right->getFilename()));

            foreach ($files as $file) {
                $asset = $this->buildGeneratedAsset($directory, $file->getFilename());

                if ($asset === null) {
                    continue;
                }

                $generated[$asset['key']] = $asset['config'];
            }
        }

        uasort($generated, fn (array $left, array $right): int => ($left['sort_order'] ?? PHP_INT_MAX) <=> ($right['sort_order'] ?? PHP_INT_MAX));

        return $generated;
    }

    private function discoverWearableAssets(): array
    {
        $assets = [];

        foreach (config('avatar.wearable_roots', []) as $root => $rootConfig) {
            $path = $this->locator->directoryPath($root);

            if ($path === null) {
                continue;
            }

            $files = array_values(array_filter(
                File::allFiles($path),
                fn ($file): bool => strtolower($file->getExtension()) === 'png',
            ));

            usort($files, fn ($left, $right): int => strnatcasecmp($left->getPathname(), $right->getPathname()));

            foreach ($files as $index => $file) {
                $relativeDirectory = trim(str_replace($path, '', $file->getPath()), DIRECTORY_SEPARATOR);
                $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
                $relativePath = trim($root.'/'.($relativeDirectory !== '' ? $relativeDirectory.'/' : '').$file->getFilename(), '/');
                $asset = $this->buildWearableAsset($relativePath, is_array($rootConfig) ? $rootConfig : [], $index);

                if ($asset === null) {
                    continue;
                }

                $assets[$asset['key']] = $asset['config'];
            }
        }

        uasort($assets, function (array $left, array $right): int {
            $leftOrder = $left['sort_order'] ?? PHP_INT_MAX;
            $rightOrder = $right['sort_order'] ?? PHP_INT_MAX;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strnatcasecmp($left['label'] ?? '', $right['label'] ?? '');
        });

        return $assets;
    }

    private function buildGeneratedAsset(string $directory, string $filename): ?array
    {
        $relativePath = "{$directory}/{$filename}";
        $group = array_search($directory, config('avatar.asset_directories', []), true);

        return match ($group) {
            'body' => $this->buildFlatBodyAsset($relativePath),
            'hair' => $this->buildFlatHairAsset($relativePath),
            default => $this->buildGeneratedAssetFromRelativePath($relativePath),
        };
    }

    private function buildGeneratedAssetFromRelativePath(string $relativePath): ?array
    {
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'svg'], true)) {
            return null;
        }

        return [
            'key' => Str::slug(pathinfo($relativePath, PATHINFO_FILENAME)),
            'config' => [
                'label' => $this->humanizeSlug(pathinfo($relativePath, PATHINFO_FILENAME)),
                'file' => str_replace('\\', '/', $relativePath),
                'type' => $extension === 'svg' ? 'svg' : 'sheet_png',
                'preview' => config('avatar.preview'),
            ],
        ];
    }

    private function buildFlatBodyAsset(string $relativePath): ?array
    {
        $base = $this->buildGeneratedAssetFromRelativePath($relativePath);

        if ($base === null) {
            return null;
        }

        $key = pathinfo($relativePath, PATHINFO_FILENAME);

        return [
            'key' => $key,
            'config' => array_replace_recursive($base['config'], [
                'description' => config('avatar.body_defaults.description'),
                'skin' => config('avatar.defaults.skin'),
                'frame_profile' => 'female',
                'hair_scale' => config('avatar.body_defaults.hair_scale', '1.00'),
                'hair_offset_y' => config('avatar.body_defaults.hair_offset_y', '0%'),
                'sort_order' => PHP_INT_MAX,
                'preview' => array_replace(config('avatar.preview'), config('avatar.body_defaults.preview', [])),
            ]),
        ];
    }

    private function buildFlatHairAsset(string $relativePath): ?array
    {
        $base = $this->buildGeneratedAssetFromRelativePath($relativePath);

        if ($base === null) {
            return null;
        }

        $key = pathinfo($relativePath, PATHINFO_FILENAME);

        return [
            'key' => $key,
            'config' => array_replace_recursive($base['config'], [
                'frame_profile' => 'female',
                'model_key' => $key,
                'model_label' => $base['config']['label'],
                'model_description' => config('avatar.hair_defaults.description'),
                'model_sort_order' => PHP_INT_MAX,
                'variant_key' => 'default',
                'variant_label' => 'Standard',
                'sort_order' => PHP_INT_MAX,
                'preview' => array_replace(config('avatar.preview'), config('avatar.hair_defaults.preview', [])),
            ]),
        ];
    }

    private function buildWearableAsset(string $relativePath, array $directoryConfig, int $index): ?array
    {
        $base = $this->buildGeneratedAssetFromRelativePath($relativePath);

        if ($base === null) {
            return null;
        }

        $directory = str_replace('\\', '/', pathinfo($relativePath, PATHINFO_DIRNAME));
        $filename = pathinfo($relativePath, PATHINFO_BASENAME);
        $frameProfile = $this->inferWearableFrameProfile($directory, $directoryConfig);

        if ($frameProfile === null && ($directoryConfig['slot'] ?? null) !== 'full_outfit') {
            return null;
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $cleanName = ltrim($baseName, '_');
        $key = Str::slug(str_replace('/', ' ', $directory).'-'.$cleanName);
        $labelPrefix = $this->wearableLabelPrefix($directory, $directoryConfig);
        $rootOrder = (int) ($directoryConfig['sort_order'] ?? 0);

        return [
            'key' => $key,
            'config' => array_replace_recursive($base['config'], [
                'label' => trim(sprintf('%s %s', $labelPrefix, $this->colorLabel($cleanName))),
                'slot' => $directoryConfig['slot'] ?? 'accessory',
                'directory_key' => $directory,
                'frame_profile' => $frameProfile,
                'variant_key' => Str::slug($cleanName),
                'variant_label' => $this->colorLabel($cleanName),
                'sort_order' => ($rootOrder * 1000) + $index,
                'preview' => array_replace(config('avatar.preview'), $directoryConfig['preview'] ?? []),
            ]),
        ];
    }

    private function buildLpcHairAssetsForSource(
        string $styleFolder,
        string $styleKey,
        string $sourceProfile,
        array $targetProfiles,
        string $styleLabel,
        string $styleDescription,
        int $styleSortOrder,
        bool $supportsVariants,
    ): array {
        $previewFile = "hairs/{$styleFolder}/{$sourceProfile}.png";

        if (! $this->locator->exists($previewFile) || $targetProfiles === []) {
            return [];
        }

        $variantFiles = [];

        if ($supportsVariants) {
            $variantDirectory = $this->locator->directoryPath("hairs/{$styleFolder}/{$sourceProfile}");
            $variantFiles = $variantDirectory !== null
                ? array_values(array_filter(
                    File::files($variantDirectory),
                    fn ($file): bool => strtolower($file->getExtension()) === 'png',
                ))
                : [];

            usort($variantFiles, fn ($left, $right): int => strnatcasecmp($left->getFilename(), $right->getFilename()));
        }

        $assets = [];
        $sortBase = $styleSortOrder === PHP_INT_MAX ? PHP_INT_MAX : $styleSortOrder * 100;

        foreach ($targetProfiles as $targetProfile) {
            if ($variantFiles === []) {
                $variantKey = 'default';
                $assetKey = "{$styleKey}--{$targetProfile}--{$variantKey}";

                $assets[$assetKey] = [
                    'label' => $styleLabel,
                    'file' => $previewFile,
                    'type' => 'sheet_png',
                    'frame_profile' => $targetProfile,
                    'model_key' => $styleKey,
                    'model_label' => $styleLabel,
                    'model_description' => $styleDescription,
                    'model_sort_order' => $styleSortOrder,
                    'variant_key' => $variantKey,
                    'variant_label' => __('onboarding.avatar.default_variant'),
                    'sort_order' => $styleSortOrder,
                    'preview' => array_replace(config('avatar.preview'), config('avatar.hair_defaults.preview', [])),
                ];

                continue;
            }

            foreach ($variantFiles as $index => $variantFile) {
                $colorKey = Str::slug(pathinfo($variantFile->getFilename(), PATHINFO_FILENAME));
                $assetKey = "{$styleKey}--{$targetProfile}--{$colorKey}";

                $assets[$assetKey] = [
                    'label' => sprintf('%s · %s', $styleLabel, $this->colorLabel($colorKey)),
                    'file' => "hairs/{$styleFolder}/{$sourceProfile}/".$variantFile->getFilename(),
                    'type' => 'sheet_png',
                    'frame_profile' => $targetProfile,
                    'model_key' => $styleKey,
                    'model_label' => $styleLabel,
                    'model_description' => $styleDescription,
                    'model_sort_order' => $styleSortOrder,
                    'variant_key' => $colorKey,
                    'variant_label' => $this->colorLabel($colorKey),
                    'sort_order' => $sortBase === PHP_INT_MAX ? PHP_INT_MAX : $sortBase + $index,
                    'preview' => array_replace(config('avatar.preview'), config('avatar.hair_defaults.preview', [])),
                ];
            }
        }

        return $assets;
    }

    private function normalizeOutfitPreset(string $key, array $preset): array
    {
        $profiles = [];

        foreach (($preset['profiles'] ?? []) as $profile => $profileConfig) {
            if (! is_array($profileConfig)) {
                continue;
            }

            $profiles[$profile] = $this->normalizeOutfitSlots($profileConfig);
        }

        return [
            'key' => $key,
            'label' => $preset['label'] ?? $this->humanizeSlug($key),
            'sort_order' => $preset['sort_order'] ?? PHP_INT_MAX,
            'slots' => $this->normalizeOutfitSlots($preset),
            'profiles' => $profiles,
        ];
    }

    private function normalizeOutfitSlots(array $source): array
    {
        $slots = $this->emptyWearableSlots();

        if (isset($source['full_outfit']) && is_string($source['full_outfit']) && $source['full_outfit'] !== '') {
            $slots['full_outfit'] = $source['full_outfit'];

            return $slots;
        }

        $pieces = $source['pieces'] ?? [];

        foreach ($this->wearableSlots() as $slot) {
            $piece = $pieces[$slot] ?? $source[$slot] ?? null;

            if (is_string($piece) && $piece !== '') {
                $slots[$slot] = $piece;
            }
        }

        return $slots;
    }

    private function emptyWearableSlots(): array
    {
        return collect($this->wearableSlots())
            ->mapWithKeys(fn (string $slot): array => [$slot => null])
            ->all();
    }

    private function wearableSlots(): array
    {
        return config('avatar.wearable_slots', []);
    }

    private function translatedText(string $key, string $fallback): string
    {
        $translated = __($key);

        return $translated === $key ? $fallback : $translated;
    }

    private function humanizeSlug(string $value): string
    {
        return Str::of($value)
            ->replace(['_', '-'], ' ')
            ->trim()
            ->title()
            ->toString();
    }

    private function colorLabel(string $value): string
    {
        $slug = Str::slug($value);

        return $this->translatedText("onboarding.avatar.colors.{$slug}", $this->humanizeSlug($slug));
    }

    private function resolvePreferredVariantKey(array $variants, string $preferredVariant): ?string
    {
        foreach ($variants as $variant) {
            if (($variant['variant_key'] ?? null) === $preferredVariant) {
                return $variant['key'] ?? null;
            }
        }

        return $variants[0]['key'] ?? null;
    }

    private function inferWearableFrameProfile(string $directory, array $directoryConfig): ?string
    {
        $segments = array_reverse(array_values(array_filter(explode('/', trim($directory, '/')))));

        foreach ($segments as $segment) {
            $alias = config('avatar.wearable_profile_aliases.'.$segment);

            if (is_string($alias) && $alias !== '') {
                return $alias === 'adult' ? 'female' : $alias;
            }
        }

        $fallback = $directoryConfig['frame_profile'] ?? null;

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    private function wearableLabelPrefix(string $directory, array $directoryConfig): string
    {
        if (isset($directoryConfig['label_prefix']) && is_string($directoryConfig['label_prefix']) && $directoryConfig['label_prefix'] !== '') {
            return $directoryConfig['label_prefix'];
        }

        $segments = array_values(array_filter(
            explode('/', trim($directory, '/')),
            fn (string $segment): bool => config('avatar.wearable_profile_aliases.'.$segment) === null,
        ));

        $labelSource = end($segments) ?: basename($directory);

        return $this->humanizeSlug((string) $labelSource);
    }
}
