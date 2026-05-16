<?php

namespace App\Models\Learning;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Training\TrainingAnswer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'answer_text',
    ];

    protected function casts(): array
    {
        return array_merge([
            'is_correct' => 'boolean',
            'metadata' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function trainingAnswers(): HasMany
    {
        return $this->hasMany(TrainingAnswer::class);
    }
}
