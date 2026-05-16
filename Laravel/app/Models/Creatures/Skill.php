<?php

namespace App\Models\Creatures;

use App\Enums\SkillTarget;
use App\Enums\SkillType;
use App\Models\Battle\BattleTurn;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Companions\Companion;
use App\Models\Companions\UserCompanionSkill;
use App\Models\Learning\LearningCategory;
use App\Models\Skills\SkillNode;
use App\Models\Skills\UserSkill;
use App\Models\User;
use App\Enums\Rarity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
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
        return array_merge([
            'type' => SkillType::class,
            'target' => SkillTarget::class,
            'rarity' => Rarity::class,
            'combat_effect' => 'array',
            'prerequisites' => 'array',
            'scaling' => 'array',
            'tags' => 'array',
            'specialization_keys' => 'array',
            'role_keys' => 'array',
            'is_passive' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function creatures(): BelongsToMany
    {
        return $this->belongsToMany(Creature::class)
            ->withPivot(['unlock_level', 'sort_order', 'is_default'])
            ->withTimestamps();
    }

    public function userCreatureSkills(): HasMany
    {
        return $this->hasMany(UserCreatureSkill::class);
    }

    public function companions(): BelongsToMany
    {
        return $this->belongsToMany(Companion::class, 'companion_skill')
            ->withPivot(['unlock_level', 'sort_order', 'is_default'])
            ->withTimestamps();
    }

    public function userCompanionSkills(): HasMany
    {
        return $this->hasMany(UserCompanionSkill::class);
    }

    public function skillNodes(): HasMany
    {
        return $this->hasMany(SkillNode::class);
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->withPivot(['skill_node_id', 'source', 'unlocked_at'])
            ->withTimestamps();
    }

    public function battleTurns(): HasMany
    {
        return $this->hasMany(BattleTurn::class);
    }
}
