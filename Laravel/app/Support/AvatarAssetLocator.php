<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class AvatarAssetLocator
{
    public function directoryPath(string $relativeDirectory): ?string
    {
        foreach ($this->sourceRoots() as $root) {
            $path = $root.'/'.$this->normalizeRelativePath($relativeDirectory);

            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    public function absolutePath(string $relativePath): ?string
    {
        $normalized = $this->normalizeRelativePath($relativePath);

        foreach ($this->sourceRoots() as $root) {
            $path = $root.'/'.$normalized;

            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function exists(string $relativePath): bool
    {
        return $this->absolutePath($relativePath) !== null;
    }

    public function url(string $relativePath): string
    {
        return route('avatar.assets.show', ['path' => $this->normalizeRelativePath($relativePath)]);
    }

    public function ensureSafePath(string $relativePath): string
    {
        $normalized = $this->normalizeRelativePath($relativePath);

        if ($normalized === '' || str_contains($normalized, '../')) {
            throw new InvalidArgumentException('Avatar asset path is invalid.');
        }

        return $normalized;
    }

    private function sourceRoots(): array
    {
        $roots = array_filter(config('avatar.source_roots', []), fn ($root): bool => is_string($root) && $root !== '');

        return array_values(array_unique($roots));
    }

    private function normalizeRelativePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }
}
