<?php

namespace App\Http\Controllers;

use App\Support\AvatarAssetLocator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarAssetController extends Controller
{
    public function show(string $path, AvatarAssetLocator $locator): BinaryFileResponse
    {
        $relativePath = $locator->ensureSafePath($path);
        $absolutePath = $locator->absolutePath($relativePath);

        abort_if($absolutePath === null, 404);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
