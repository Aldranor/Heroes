<?php

namespace App\Models\Battle;

use App\Enums\BattleStatus;
use App\Models\User;
use App\Models\World\AdventureNode;
use App\Models\World\Enemy;
use App\Models\World\Level;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battle extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BattleStatus::class,
            'party' => 'array',
            'state' => 'array',
            'metadata' => 'array',
            'reward_claimed' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    public function adventureNode(): BelongsTo
    {
        return $this->belongsTo(AdventureNode::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function turns(): HasMany
    {
        return $this->hasMany(BattleTurn::class)->orderBy('turn_number');
    }
}
