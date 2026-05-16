<?php

namespace App\Models\Concerns;

use Illuminate\Support\Arr;

trait HasLocalizedAttributes
{
    public function getAttributeValue($key): mixed
    {
        $value = parent::getAttributeValue($key);

        if (! $this->isTranslatableAttribute($key)) {
            return $value;
        }

        return $this->resolveLocalizedAttribute($key, $value);
    }

    public function getTranslation(string $attribute, ?string $locale = null): mixed
    {
        return $this->resolveLocalizedAttribute(
            $attribute,
            parent::getAttributeValue($attribute),
            $locale,
        );
    }

    public function getTranslations(string $attribute): array
    {
        if (! $this->isTranslatableAttribute($attribute)) {
            return [];
        }

        return parent::getAttributeValue($attribute.'_translations') ?? [];
    }

    protected function localizedAttributeCasts(): array
    {
        return collect($this->getTranslatableAttributes())
            ->mapWithKeys(fn (string $attribute) => [$attribute.'_translations' => 'array'])
            ->all();
    }

    protected function getTranslatableAttributes(): array
    {
        return property_exists($this, 'translatable')
            ? $this->translatable
            : [];
    }

    protected function isTranslatableAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatableAttributes(), true);
    }

    protected function resolveLocalizedAttribute(string $attribute, mixed $fallback, ?string $locale = null): mixed
    {
        if (! $this->isTranslatableAttribute($attribute)) {
            return $fallback;
        }

        $translations = parent::getAttributeValue($attribute.'_translations');

        if (! is_array($translations) || $translations === []) {
            return $fallback;
        }

        foreach ($this->localesToCheck($locale) as $candidate) {
            if (array_key_exists($candidate, $translations) && filled($translations[$candidate])) {
                return $translations[$candidate];
            }
        }

        return Arr::first(
            array_filter($translations, static fn (mixed $value): bool => filled($value)),
        ) ?? $fallback;
    }

    protected function localesToCheck(?string $locale = null): array
    {
        $candidates = array_filter([
            $locale,
            app()->getLocale(),
            config('app.fallback_locale'),
        ]);

        $normalized = [];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $normalized[] = $candidate;

            $parts = preg_split('/[-_]/', $candidate);
            $language = $parts[0] ?? null;

            if (filled($language)) {
                $normalized[] = $language;
            }
        }

        return array_values(array_unique($normalized));
    }
}
