<?php

namespace App\Models\Learning;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Creatures\Creature;
use App\Models\Lessons\Lesson;
use App\Models\Quests\Quest;
use App\Models\World\Enemy;
use App\Models\World\Scenario;
use App\Models\World\Zone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningDomain extends Model
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
            'is_active' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function categories(): HasMany
    {
        return $this->hasMany(LearningCategory::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(LearningTopic::class)->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function creatures(): HasMany
    {
        return $this->hasMany(Creature::class);
    }

    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->orderBy('sort_order');
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }
}
