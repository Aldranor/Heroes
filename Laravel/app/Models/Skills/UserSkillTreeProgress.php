<?php

namespace App\Models\Skills;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillTreeProgress extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillTree(): BelongsTo
    {
        return $this->belongsTo(SkillTree::class);
    }

    public function skillNode(): BelongsTo
    {
        return $this->belongsTo(SkillNode::class);
    }
}
