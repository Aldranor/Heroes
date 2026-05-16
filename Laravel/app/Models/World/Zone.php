<?php

namespace App\Models\World;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Learning\LearningDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
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
        return $this->localizedAttributeCasts();
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function scenarioIntro(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'scenario_intro_id');
    }

    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class)->orderBy('level_number');
    }
}
