<?php

namespace App\Models\Skills;

use App\Models\Creatures\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillNode extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'prerequisite_node_ids' => 'array',
        ];
    }

    public function skillTree(): BelongsTo
    {
        return $this->belongsTo(SkillTree::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserSkillTreeProgress::class);
    }
}
