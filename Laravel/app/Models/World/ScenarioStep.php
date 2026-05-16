<?php

namespace App\Models\World;

use App\Enums\ScenarioActionType;
use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioStep extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'speaker_name',
        'text',
    ];

    protected function casts(): array
    {
        return array_merge([
            'action_type' => ScenarioActionType::class,
            'action_payload' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
