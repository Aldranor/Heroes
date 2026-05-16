<?php

namespace App\Models\Lessons;

use App\Enums\FlashcardProgressStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFlashcardProgress extends Model
{
    use HasFactory;

    protected $table = 'user_flashcard_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => FlashcardProgressStatus::class,
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }
}
