<?php

namespace Tests\Unit\Support;

use App\Support\AvatarAssetCatalog;
use Tests\TestCase;

class AvatarAssetCatalogTest extends TestCase
{
    public function test_it_auto_detects_body_assets_from_the_pack_defaults(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $asset = $catalog->bodyAsset('human-female');

        $this->assertNotNull($asset);
        $this->assertSame('Humaine', $asset['label']);
        $this->assertSame('Agile et équilibrée', $asset['description']);
        $this->assertSame('bodies/Human/Female/Idle.png', $asset['file']);
        $this->assertSame('#F7D7C4', $asset['skin']);
        $this->assertSame(config('avatar.body_defaults.preview'), $asset['preview']);
    }

    public function test_it_exposes_humanoid_body_assets(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $asset = $catalog->bodyAsset('lizard-female');

        $this->assertNotNull($asset);
        $this->assertSame('Saurienne', $asset['label']);
        $this->assertSame('bodies/Humanoid Animals/Lizardman/Female/Idle.png', $asset['file']);
    }

    public function test_it_auto_detects_hair_assets_with_model_and_variant_metadata(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $asset = $catalog->hairAsset('bob--female--black');

        $this->assertNotNull($asset);
        $this->assertSame('hairs/bob/female/black.png', $asset['file']);
        $this->assertSame('bob', $asset['model_key']);
        $this->assertSame('black', $asset['variant_key']);
        $this->assertSame('Carré', $asset['model_label']);
        $this->assertSame('Noir', $asset['variant_label']);
    }

    public function test_it_groups_hairs_by_model(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $models = $catalog->hairModels();

        $this->assertArrayHasKey('bob', $models);
        $this->assertSame('Carré', $models['bob']['label']);
        $this->assertSame('Net et lisible', $models['bob']['description']);
        $this->assertContains('female', $models['bob']['supported_profiles']);
        $this->assertContains('male', $models['bob']['supported_profiles']);
        $this->assertNotEmpty($models['bob']['profiles']['female']['variants']);
        $this->assertSame('bob--female--black', $models['bob']['profiles']['female']['default_variant_key']);
    }

    public function test_it_auto_detects_wearable_assets_from_multiple_directories(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $asset = $catalog->wearableAsset('torso-clothes-tunic-female-blue');

        $this->assertNotNull($asset);
        $this->assertSame('torso/clothes/tunic/female/blue.png', $asset['file']);
        $this->assertSame('top', $asset['slot']);
        $this->assertSame('female', $asset['frame_profile']);
        $this->assertSame('blue', $asset['variant_key']);
        $this->assertSame('Bleu', $asset['variant_label']);
    }

    public function test_it_builds_piece_based_outfit_presets_from_detected_assets(): void
    {
        $catalog = app(AvatarAssetCatalog::class);

        $preset = $catalog->outfitPreset('strategist');

        $this->assertNotNull($preset);
        $this->assertSame('Stratège', $preset['label']);
        $this->assertSame('cape-solid-female-blue', $preset['profiles']['female']['back']);
        $this->assertSame('feet-boots-female-blue', $preset['profiles']['female']['shoes']);
        $this->assertSame('legs-pants-female-blue', $preset['profiles']['female']['pants']);
        $this->assertSame('torso-clothes-tunic-female-blue', $preset['profiles']['female']['top']);
        $this->assertSame('cape-solid-male-blue', $preset['profiles']['male']['back']);
        $this->assertSame('torso-clothes-longsleeve-male-blue', $preset['profiles']['male']['top']);
    }
}
