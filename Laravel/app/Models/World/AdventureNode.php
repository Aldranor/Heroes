<?php

namespace App\Models\World;

use App\Models\Learning\LearningCategory;
use App\Models\Battle\Battle;
use App\Models\Lessons\Lesson;
use App\Models\Training\TrainingSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdventureNode extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_start' => 'boolean',
            'is_repeatable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(AdventureMap::class, 'adventure_map_id');
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function outgoingPaths(): HasMany
    {
        return $this->hasMany(AdventurePath::class, 'from_adventure_node_id')->orderBy('sort_order');
    }

    public function incomingPaths(): HasMany
    {
        return $this->hasMany(AdventurePath::class, 'to_adventure_node_id')->orderBy('sort_order');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserAdventureNodeProgress::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function dialogues(): HasMany
    {
        return $this->hasMany(AdventureDialogue::class);
    }
}
