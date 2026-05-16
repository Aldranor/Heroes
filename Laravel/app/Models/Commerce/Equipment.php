<?php

namespace App\Models\Commerce;

use App\Enums\EquipmentTargetType;
use App\Enums\EquipmentType;
use App\Enums\Rarity;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Quests\Quest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $table = 'equipment';

    protected $guarded = [];

    protected array $translatable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return array_merge([
            'type' => EquipmentType::class,
            'target_type' => EquipmentTargetType::class,
            'rarity' => Rarity::class,
            'stat_bonus' => 'array',
        ], $this->localizedAttributeCasts());
    }

    public function userEquipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }

    public function rewardQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'reward_equipment_id');
    }
}
