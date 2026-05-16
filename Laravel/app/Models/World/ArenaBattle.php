<?php

namespace App\Models\World;

use App\Enums\BattleStatus;
use App\Models\Battle\Battle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArenaBattle extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BattleStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function arenaRun(): BelongsTo
    {
        return $this->belongsTo(ArenaRun::class);
    }

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
