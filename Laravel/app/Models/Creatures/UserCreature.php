<?php

namespace App\Models\Creatures;

use App\Models\Commerce\UserEquipment;
use App\Models\Learning\LearningCategory;
use App\Models\Training\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserCreature extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'acquired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creature(): BelongsTo
    {
        return $this->belongsTo(Creature::class);
    }

    public function unlockedSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_creature_skills')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function userCreatureSkills(): HasMany
    {
        return $this->hasMany(UserCreatureSkill::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function equippedEquipment(): MorphMany
    {
        return $this->morphMany(UserEquipment::class, 'equipped_to');
    }

    public function maxHp(): int
    {
        return $this->creature->base_hp + (($this->level - 1) * 12);
    }

    public function attackStat(): int
    {
        return $this->creature->base_attack + (($this->level - 1) * 3);
    }

    public function defenseStat(): int
    {
        return $this->creature->base_defense + (($this->level - 1) * 2);
    }

    public function speedStat(): int
    {
        return $this->creature->base_speed + (($this->level - 1) * 2);
    }

    public function isSpecializedFor(?LearningCategory $category): bool
    {
        if (! $category || ! $this->creature->learning_category_id) {
            return false;
        }

        return $this->creature->learning_category_id === $category->id;
    }
}
