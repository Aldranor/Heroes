<?php

namespace App\Models\Commerce;

use App\Enums\Currency;
use App\Enums\ShopItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ShopItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'item_type' => ShopItemType::class,
            'currency' => Currency::class,
            'is_active' => 'boolean',
        ];
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
