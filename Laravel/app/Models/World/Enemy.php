<?php

namespace App\Models\World;

use App\Enums\EnemyType;
use App\Models\Battle\Battle;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Learning\LearningDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enemy extends Model
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
            'type' => EnemyType::class,
            'is_boss' => 'boolean',
            'skill_set' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }
}
