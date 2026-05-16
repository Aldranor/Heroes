<?php

namespace App\Models\Avatar;

use App\Models\Commerce\UserEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Avatar extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'equipped_items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userAvatarItems(): HasMany
    {
        return $this->hasMany(UserAvatarItem::class, 'user_id', 'user_id');
    }

    public function equippedEquipment(): MorphMany
    {
        return $this->morphMany(UserEquipment::class, 'equipped_to');
    }
}
