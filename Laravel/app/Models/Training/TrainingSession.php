<?php

namespace App\Models\Training;

use App\Enums\TrainingSessionStatus;
use App\Models\Companions\UserCompanion;
use App\Models\Creatures\UserCreature;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\User;
use App\Models\World\AdventureNode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TrainingSessionStatus::class,
            'question_ids' => 'array',
            'metadata' => 'array',
            'perfect_session' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function userCreature(): BelongsTo
    {
        return $this->belongsTo(UserCreature::class);
    }

    public function userCompanion(): BelongsTo
    {
        return $this->belongsTo(UserCompanion::class);
    }

    public function adventureNode(): BelongsTo
    {
        return $this->belongsTo(AdventureNode::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TrainingAnswer::class);
    }
}
