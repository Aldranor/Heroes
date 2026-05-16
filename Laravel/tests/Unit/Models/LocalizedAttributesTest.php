<?php

namespace Tests\Unit\Models;

use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_attribute_uses_current_locale_when_translation_exists(): void
    {
        $domain = LearningDomain::query()->create([
            'name' => 'Driving',
            'slug' => 'driving',
        ]);

        $category = LearningCategory::query()->create([
            'learning_domain_id' => $domain->id,
            'name' => 'Priority',
            'name_translations' => [
                'fr' => 'Priorités',
                'en' => 'Priority',
            ],
            'slug' => 'priority',
        ]);

        app()->setLocale('fr');
        $this->assertSame('Priorités', $category->fresh()->name);

        app()->setLocale('en');
        $this->assertSame('Priority', $category->fresh()->name);
    }

    public function test_model_attribute_falls_back_to_base_value_when_translation_is_missing(): void
    {
        $domain = LearningDomain::query()->create([
            'name' => 'Driving',
            'slug' => 'driving',
        ]);

        $category = LearningCategory::query()->create([
            'learning_domain_id' => $domain->id,
            'name' => 'Mixed',
            'slug' => 'mixed',
        ]);

        app()->setLocale('fr');

        $this->assertSame('Mixed', $category->fresh()->name);
    }
}
