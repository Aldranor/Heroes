<?php

declare(strict_types=1);

$outputDirectory = dirname(__DIR__) . '/public/avatars';

if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0777, true) && ! is_dir($outputDirectory)) {
    throw new RuntimeException(sprintf('Unable to create directory: %s', $outputDirectory));
}

$size = 512;

$skins = [
    'light' => '#F7D7C4',
    'warm' => '#E8B998',
    'tan' => '#C98A66',
    'deep' => '#8F5F46',
];

$hairColors = [
    'chestnut' => '#8C5A43',
    'auburn' => '#C96D42',
    'midnight' => '#2B2330',
    'teal' => '#2F5D62',
];

$outfits = [
    'trailblazer' => [
        'base' => '#D97745',
        'shade' => '#9A4E25',
        'detail' => '#F6D28B',
    ],
    'cartographer' => [
        'base' => '#3F7C85',
        'shade' => '#254A50',
        'detail' => '#CBE4E8',
    ],
    'sentinel' => [
        'base' => '#5B8C5A',
        'shade' => '#345132',
        'detail' => '#D7E7C6',
    ],
];

foreach ($skins as $key => $hex) {
    createTransparentCanvas($size, $outputDirectory."/body-{$key}.png", function ($image) use ($hex): void {
        $skin = allocateColor($image, $hex);
        $shadow = allocateColor($image, darken($hex, 18));

        imagefilledellipse($image, 256, 168, 164, 196, $skin);
        imagefilledrectangle($image, 224, 240, 288, 320, $skin);
        imagefilledellipse($image, 256, 410, 292, 212, $skin);

        imagefilledellipse($image, 256, 286, 116, 44, $shadow);
        imagefilledellipse($image, 256, 500, 340, 180, $shadow);
    });
}

foreach ($hairColors as $key => $hex) {
    createTransparentCanvas($size, $outputDirectory."/hair-{$key}.png", function ($image) use ($hex): void {
        $base = allocateColor($image, $hex);
        $shade = allocateColor($image, darken($hex, 18));

        imagefilledarc($image, 256, 150, 190, 196, 180, 360, $base, IMG_ARC_PIE);
        imagefilledellipse($image, 178, 182, 52, 120, $base);
        imagefilledellipse($image, 334, 182, 52, 120, $base);
        imagefilledpolygon($image, [
            176, 146,
            222, 124,
            242, 162,
            272, 120,
            336, 150,
            314, 206,
            198, 206,
        ], $shade);
        imagefilledellipse($image, 256, 126, 148, 66, $shade);
    });
}

foreach ($outfits as $key => $palette) {
    createTransparentCanvas($size, $outputDirectory."/outfit-{$key}.png", function ($image) use ($key, $palette): void {
        $base = allocateColor($image, $palette['base']);
        $shade = allocateColor($image, $palette['shade']);
        $detail = allocateColor($image, $palette['detail']);
        $transparent = allocateTransparent($image);

        imagefilledpolygon($image, [
            118, 314,
            394, 314,
            438, 512,
            74, 512,
        ], $base);

        imagefilledellipse($image, 256, 336, 200, 80, $shade);
        imagefilledrectangle($image, 188, 272, 324, 348, $shade);

        imagefilledellipse($image, 256, 282, 108, 56, $detail);
        imagefilledrectangle($image, 204, 282, 308, 360, $transparent);

        if ($key === 'trailblazer') {
            imagefilledpolygon($image, [
                214, 330,
                258, 330,
                306, 512,
                262, 512,
            ], $detail);
            imagefilledellipse($image, 214, 390, 24, 24, $shade);
        }

        if ($key === 'cartographer') {
            imagefilledellipse($image, 256, 396, 86, 86, $detail);
            imagefilledellipse($image, 256, 396, 38, 38, $shade);
            imageline($image, 256, 354, 256, 438, $shade);
            imageline($image, 214, 396, 298, 396, $shade);
        }

        if ($key === 'sentinel') {
            imagefilledpolygon($image, [
                164, 314,
                348, 314,
                306, 394,
                206, 394,
            ], $detail);
            imagefilledellipse($image, 256, 434, 94, 94, $detail);
            imagefilledellipse($image, 256, 434, 52, 52, $shade);
        }
    });
}

echo "Avatar assets generated in {$outputDirectory}\n";

function createTransparentCanvas(int $size, string $path, callable $drawer): void
{
    $image = imagecreatetruecolor($size, $size);

    imagealphablending($image, false);
    imagesavealpha($image, true);

    $transparent = allocateTransparent($image);
    imagefill($image, 0, 0, $transparent);

    imagealphablending($image, true);

    $drawer($image);

    imagepng($image, $path);
}

function allocateTransparent($image): int
{
    return imagecolorallocatealpha($image, 0, 0, 0, 127);
}

function allocateColor($image, string $hex, int $alpha = 0): int
{
    [$red, $green, $blue] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

    return imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
}

function darken(string $hex, int $amount): string
{
    [$red, $green, $blue] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

    $red = max(0, $red - $amount);
    $green = max(0, $green - $amount);
    $blue = max(0, $blue - $amount);

    return sprintf('#%02X%02X%02X', $red, $green, $blue);
}
