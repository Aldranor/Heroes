<?php

namespace App\Models\Lessons;

use App\Enums\LessonStatus;
use App\Enums\QuestionDifficulty;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\LearningTopic;
use App\Models\Learning\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'title',
        'summary',
    ];

    protected function casts(): array
    {
        return array_merge([
            'status' => LessonStatus::class,
            'difficulty' => QuestionDifficulty::class,
            'unlocks_training' => 'boolean',
            'published_at' => 'datetime',
        ], $this->localizedAttributeCasts());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LessonStatus::Published);
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

    public function sections(): HasMany
    {
        return $this->hasMany(LessonSection::class)->orderBy('sort_order');
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class)->orderBy('sort_order');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserLessonProgress::class);
    }
}
