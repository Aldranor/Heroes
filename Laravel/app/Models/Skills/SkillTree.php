<?php

namespace App\Models\Skills;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillTree extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }

    public function skillNodes(): HasMany
    {
        return $this->hasMany(SkillNode::class)->orderBy('column_index')->orderBy('row_index');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserSkillTreeProgress::class);
    }
}
