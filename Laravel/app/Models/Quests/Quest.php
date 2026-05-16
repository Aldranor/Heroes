<?php

namespace App\Models\Quests;

use App\Enums\QuestObjectiveType;
use App\Enums\QuestType;
use App\Models\Avatar\AvatarItem;
use App\Models\Commerce\Equipment;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Creatures\Creature;
use App\Models\Learning\LearningDomain;
use App\Models\World\Npc;
use App\Models\World\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'title',
        'description',
    ];

    protected function casts(): array
    {
        return array_merge([
            'type' => QuestType::class,
            'objective_type' => QuestObjectiveType::class,
            'objective_payload' => 'array',
            'is_repeatable' => 'boolean',
            'is_daily' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function learningDomain(): BelongsTo
    {
        return $this->belongsTo(LearningDomain::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function rewardEquipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'reward_equipment_id');
    }

    public function rewardAvatarItem(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'reward_avatar_item_id');
    }

    public function rewardCreature(): BelongsTo
    {
        return $this->belongsTo(Creature::class, 'reward_creature_id');
    }

    public function scenarioIntro(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'scenario_intro_id');
    }

    public function scenarioComplete(): BelongsTo
    {
        return $this->belongsTo(Scenario::class, 'scenario_complete_id');
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }
}
