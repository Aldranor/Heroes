<?php

namespace App\Models\Lessons;

use App\Enums\LessonSectionType;
use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSection extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'title',
        'body',
    ];

    protected function casts(): array
    {
        return array_merge([
            'type' => LessonSectionType::class,
            'metadata' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
