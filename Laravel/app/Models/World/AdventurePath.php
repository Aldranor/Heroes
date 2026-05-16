<?php

namespace App\Models\World;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdventurePath extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(AdventureMap::class, 'adventure_map_id');
    }

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(AdventureNode::class, 'from_adventure_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(AdventureNode::class, 'to_adventure_node_id');
    }
}
