<?php

namespace App\Models\Learning;

use App\Models\Companions\Companion;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Creatures\Creature;
use App\Models\Lessons\Lesson;
use App\Models\Training\TrainingSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCategory extends Model
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
            'is_mixed' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function creatures(): HasMany
    {
        return $this->hasMany(Creature::class);
    }

    public function companions(): HasMany
    {
        return $this->hasMany(Companion::class, 'specialization_category_id');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
