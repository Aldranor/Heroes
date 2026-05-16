<?php

namespace App\Models\Lessons;

use App\Enums\LessonProgressStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLessonProgress extends Model
{
    use HasFactory;

    protected $table = 'user_lesson_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => LessonProgressStatus::class,
            'quiz_passed' => 'boolean',
            'quiz_answers' => 'array',
            'started_at' => 'datetime',
            'training_unlocked_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
