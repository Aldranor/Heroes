<?php

namespace App\Models\World;

use App\Models\Learning\LearningDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class World extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function introScenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'intro_scenario_id');
    }

    public function maps(): HasMany
    {
        return $this->hasMany(AdventureMap::class)->orderBy('sort_order');
    }
}
