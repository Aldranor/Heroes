<?php

namespace App\Models\Battle;

use App\Enums\BattleActorType;
use App\Models\Creatures\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleTurn extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'actor_type' => BattleActorType::class,
            'result' => 'array',
        ];
    }

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
