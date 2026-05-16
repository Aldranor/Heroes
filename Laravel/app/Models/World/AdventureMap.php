<?php

namespace App\Models\World;

use App\Models\Learning\LearningCategory;
use App\Models\Lessons\Lesson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdventureMap extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function introScenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'intro_scenario_id');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(AdventureNode::class)->orderBy('sort_order');
    }

    public function paths(): HasMany
    {
        return $this->hasMany(AdventurePath::class)->orderBy('sort_order');
    }

    public function dialogues(): HasMany
    {
        return $this->hasMany(AdventureDialogue::class)->orderBy('sort_order');
    }
}
