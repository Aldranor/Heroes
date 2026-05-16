<?php

namespace App\Models\World;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Quests\Quest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Npc extends Model
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

    public function scenarioSteps(): HasMany
    {
        return $this->hasMany(ScenarioStep::class);
    }

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }
}
