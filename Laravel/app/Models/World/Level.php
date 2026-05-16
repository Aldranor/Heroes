<?php

namespace App\Models\World;

use App\Models\Battle\Battle;
use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'name',
    ];

    protected function casts(): array
    {
        return array_merge([
            'is_boss_level' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function unlocksZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'unlocks_zone_id');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserLevelProgress::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }
}
