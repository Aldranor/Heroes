<?php

namespace App\Models\Lessons;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashcard extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'front',
        'back',
    ];

    protected function casts(): array
    {
        return $this->localizedAttributeCasts();
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(UserFlashcardProgress::class);
    }
}
