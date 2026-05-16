<?php

namespace App\Models\Creatures;

use App\Enums\CreatureAcquisitionMethod;
use App\Enums\Rarity;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creature extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'name',
        'description',
        'visual_theme',
        'personality',
    ];

    protected function casts(): array
    {
        return array_merge([
            'rarity' => Rarity::class,
            'starter_allowed' => 'boolean',
            'acquisition_method' => CreatureAcquisitionMethod::class,
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function defaultSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'default_skill_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot(['unlock_level', 'sort_order', 'is_default'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function userCreatures(): HasMany
    {
        return $this->hasMany(UserCreature::class);
    }
}
