<?php

namespace App\Models\Companions;

use App\Enums\CompanionAcquisitionMethod;
use App\Enums\CompanionRole;
use App\Enums\Rarity;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Creatures\Skill;
use App\Models\Learning\LearningCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Companion extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return array_merge([
            'role' => CompanionRole::class,
            'rarity' => Rarity::class,
            'acquisition_method' => CompanionAcquisitionMethod::class,
            'build_tags' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function specializationCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'specialization_category_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'companion_skill')
            ->withPivot(['unlock_level', 'sort_order', 'is_default'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function userCompanions(): HasMany
    {
        return $this->hasMany(UserCompanion::class);
    }

    public function uniquePassiveSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'unique_passive_skill_id');
    }

    public function signatureSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'signature_skill_id');
    }

    public function spriteUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->sprite_path
                ? (str_starts_with($this->sprite_path, 'companions/')
                    ? asset('images/' . $this->sprite_path)
                    : asset('storage/' . $this->sprite_path))
                : asset('assets/companions/placeholder.png'),
        );
    }
}
