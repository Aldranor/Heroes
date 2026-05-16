<?php

namespace App\Models\Avatar;

use App\Enums\AvatarItemType;
use App\Enums\Rarity;
use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvatarItem extends Model
{
    use HasFactory;
    use HasLocalizedAttributes;

    protected $guarded = [];

    protected array $translatable = [
        'name',
    ];

    protected function casts(): array
    {
        return array_merge([
            'type' => AvatarItemType::class,
            'rarity' => Rarity::class,
            'is_cosmetic_only' => 'boolean',
        ], $this->localizedAttributeCasts());
    }

    public function userAvatarItems(): HasMany
    {
        return $this->hasMany(UserAvatarItem::class);
    }
}
