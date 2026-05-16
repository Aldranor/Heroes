<?php

namespace App\Models\Companions;

use App\Models\Creatures\Skill;
use App\Models\Learning\LearningCategory;
use App\Models\Training\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCompanion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'combat_loadout' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(Companion::class);
    }

    public function unlockedSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_companion_skills')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function userCompanionSkills(): HasMany
    {
        return $this->hasMany(UserCompanionSkill::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function maxHp(): int
    {
        return $this->companion->base_hp + (($this->level - 1) * 10);
    }

    public function attackStat(): int
    {
        return $this->companion->base_attack + (($this->level - 1) * 3);
    }

    public function defenseStat(): int
    {
        return $this->companion->base_defense + (($this->level - 1) * 2);
    }

    public function speedStat(): int
    {
        return $this->companion->base_speed + (($this->level - 1) * 2);
    }

    public function isSpecializedFor(?LearningCategory $category): bool
    {
        if (! $category || ! $this->companion->specialization_category_id) {
            return false;
        }

        return $this->companion->specialization_category_id === $category->id;
    }
}
