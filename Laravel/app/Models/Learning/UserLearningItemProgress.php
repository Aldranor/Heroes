<?php

namespace App\Models\Learning;

use App\Enums\MasteryStatus;
use App\Models\Lessons\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLearningItemProgress extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mastery_status' => MasteryStatus::class,
            'metadata' => 'array',
            'last_interacted_at' => 'datetime',
            'mastered_at' => 'datetime',
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

    public function learningTopic(): BelongsTo
    {
        return $this->belongsTo(LearningTopic::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
