<?php

namespace App\Support;

final class SpriteAnimationMap
{
    public static function profile(?string $name, int $rows = 0, int $columns = 0): array
    {
        if (! is_string($name) || $name === '') {
            return [];
        }

        $profile = config("sprites.profiles.{$name}", []);
        if (! is_array($profile) || $profile === []) {
            return [];
        }

        return self::resolveProfile($profile, $rows, $columns, null, $name);
    }

    public static function resolveSet(?string $profileName, int $rows, int $columns, string $facing): array
    {
        $states = ['idle', 'combat_idle', 'combat_attack', 'hurt', 'dead', 'walk', 'run'];
        $resolved = [];

        foreach ($states as $state) {
            $animation = self::resolveAnimation($profileName, $state, $facing, $rows, $columns);
            if ($animation !== null) {
                $resolved[$state] = $animation;
            }
        }

        return $resolved;
    }

    public static function resolveAnimation(?string $profileName, string $state, string $facing, int $rows, int $columns): ?array
    {
        $profile = self::profile($profileName, $rows, $columns);
        if ($profile === []) {
            return null;
        }

        return self::resolveAnimationFromProfile($profile, $state, $facing, $rows, $columns);
    }

    private static function resolveAnimationFromProfile(array $profile, string $state, string $facing, int $rows, int $columns): ?array
    {
        if ($profile === []) {
            return null;
        }

        $mappedStates = $profile['mapped_states'] ?? null;
        if (is_array($mappedStates) && $mappedStates !== [] && ! in_array($state, $mappedStates, true)) {
            return self::resolveFallback($profile, $state, $facing, $rows, $columns);
        }

        $direction = self::directionKeyForFacing($facing);
        $animation = data_get($profile, "animations.{$state}");
        $directionConfig = data_get($animation, "directions.{$direction}")
            ?? data_get($animation, 'directions.left')
            ?? data_get($animation, 'directions.front');

        if (! is_array($animation) || ! is_array($directionConfig)) {
            return self::resolveFallback($profile, $state, $facing, $rows, $columns);
        }

        $row = (int) ($directionConfig['row'] ?? 0);
        $startColumn = (int) ($directionConfig['start_column'] ?? 0);
        $sequence = array_values(array_map('intval', $animation['sequence'] ?? [0]));
        $lastColumn = $startColumn + max($sequence);

        if ($row >= $rows || $startColumn >= $columns || $lastColumn >= $columns) {
            return self::resolveFallback($profile, $state, $facing, $rows, $columns);
        }

        return [
            'fps' => max(1, (int) ($animation['fps'] ?? 8)),
            'loop' => (bool) ($animation['loop'] ?? true),
            'sequence' => $sequence,
            'row' => $row,
            'start_column' => $startColumn,
        ];
    }

    private static function resolveFallback(array $profile, string $state, string $facing, int $rows, int $columns): ?array
    {
        $fallbackVariant = $profile['fallback_variant'] ?? null;
        $profileName = $profile['profile_name'] ?? null;
        if (is_string($fallbackVariant) && $fallbackVariant !== '' && is_string($profileName) && $profileName !== '') {
            $baseProfile = config("sprites.profiles.{$profileName}", []);
            if (is_array($baseProfile) && $baseProfile !== [] && ($profile['variant_name'] ?? null) !== $fallbackVariant) {
                $resolvedFallback = self::resolveProfile($baseProfile, $rows, $columns, $fallbackVariant, $profileName);

                return self::resolveAnimationFromProfile($resolvedFallback, $state, $facing, $rows, $columns);
            }
        }

        $fallbackProfile = $profile['fallback_profile'] ?? null;

        if (! is_string($fallbackProfile) || $fallbackProfile === '') {
            return null;
        }

        return self::resolveAnimation($fallbackProfile, $state, $facing, $rows, $columns);
    }

    private static function directionKeyForFacing(string $facing): string
    {
        return match ($facing) {
            'right' => 'right',
            'front', 'down', 'south' => 'front',
            'back', 'up', 'north' => 'back',
            default => 'left',
        };
    }

    private static function resolveProfile(array $profile, int $rows, int $columns, ?string $preferredVariant = null, ?string $profileName = null): array
    {
        if ($profileName !== null) {
            $profile['profile_name'] = $profileName;
        }

        $extensions = $profile['extensions'] ?? null;
        if (is_array($extensions) && $extensions !== []) {
            return self::resolveExtendedProfile($profile, $extensions, $rows, $columns);
        }

        $variants = $profile['variants'] ?? null;
        if (! is_array($variants) || $variants === []) {
            return $profile;
        }

        $variantName = $preferredVariant;
        if (! is_string($variantName) || $variantName === '' || ! isset($variants[$variantName])) {
            $variantName = self::matchVariant($profile, $rows, $columns);
        }

        if (! is_string($variantName) || $variantName === '' || ! is_array($variants[$variantName] ?? null)) {
            if ($profileName !== null) {
                $profile['profile_name'] = $profileName;
            }

            return $profile;
        }

        $resolved = array_replace($profile, $variants[$variantName]);
        $resolved['variants'] = $variants;
        $resolved['variant_name'] = $variantName;

        if ($profileName !== null) {
            $resolved['profile_name'] = $profileName;
        }

        return $resolved;
    }

    private static function resolveExtendedProfile(array $profile, array $extensions, int $rows, int $columns): array
    {
        foreach ($extensions as $extension) {
            if (! is_array($extension) || ! self::variantMatches($extension, $rows, $columns)) {
                continue;
            }

            $resolved = $profile;
            if (isset($extension['animations']) && is_array($extension['animations'])) {
                $resolved['animations'] = array_replace($profile['animations'] ?? [], $extension['animations']);
            }

            foreach ($extension as $key => $value) {
                if (in_array($key, ['animations', 'match'], true)) {
                    continue;
                }

                $resolved[$key] = $value;
            }

            return $resolved;
        }

        return $profile;
    }

    private static function matchVariant(array $profile, int $rows, int $columns): ?string
    {
        $variants = $profile['variants'] ?? [];
        foreach ($variants as $name => $variant) {
            if (is_string($name) && is_array($variant) && self::variantMatches($variant, $rows, $columns)) {
                return $name;
            }
        }

        $defaultVariant = $profile['default_variant'] ?? null;
        if (is_string($defaultVariant) && isset($variants[$defaultVariant])) {
            return $defaultVariant;
        }

        $firstKey = array_key_first($variants);

        return is_string($firstKey) ? $firstKey : null;
    }

    private static function variantMatches(array $variant, int $rows, int $columns): bool
    {
        $match = $variant['match'] ?? null;
        if (! is_array($match) || $match === []) {
            return true;
        }

        if (isset($match['min_rows']) && $rows < (int) $match['min_rows']) {
            return false;
        }

        if (isset($match['max_rows']) && $rows > (int) $match['max_rows']) {
            return false;
        }

        if (isset($match['min_columns']) && $columns < (int) $match['min_columns']) {
            return false;
        }

        if (isset($match['max_columns']) && $columns > (int) $match['max_columns']) {
            return false;
        }

        return true;
    }
}
