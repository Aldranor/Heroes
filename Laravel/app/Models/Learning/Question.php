<?php

namespace App\Models\Learning;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Lessons\Lesson;
use App\Models\Training\TrainingAnswer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'question_text',
        'explanation',
    ];

    protected function casts(): array
    {
        return array_merge([
            'difficulty' => QuestionDifficulty::class,
            'status' => QuestionStatus::class,
            'type' => QuestionType::class,
            'metadata' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function learningCategory(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('sort_order');
    }

    public function correctAnswer(): HasOne
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function trainingAnswers(): HasMany
    {
        return $this->hasMany(TrainingAnswer::class);
    }
}
