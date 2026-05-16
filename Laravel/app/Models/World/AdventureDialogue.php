<?php

namespace App\Models\World;

use App\Enums\AdventureDialogueTrigger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdventureDialogue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trigger_type' => AdventureDialogueTrigger::class,
            'is_active' => 'boolean',
            'script' => 'array',
            'metadata' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(AdventureMap::class, 'adventure_map_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(AdventureNode::class, 'adventure_node_id');
    }
}
