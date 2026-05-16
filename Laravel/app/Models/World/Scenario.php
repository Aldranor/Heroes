<?php

namespace App\Models\World;

use App\Enums\ScenarioTriggerType;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Learning\LearningDomain;
use App\Models\Quests\Quest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'title',
    ];

    protected function casts(): array
    {
        return array_merge([
            'trigger_type' => ScenarioTriggerType::class,
            'is_active' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ScenarioStep::class)->orderBy('sort_order');
    }

    public function introQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'scenario_intro_id');
    }

    public function completionQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'scenario_complete_id');
    }
}
