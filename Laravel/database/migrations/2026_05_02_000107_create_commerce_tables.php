<?php

use App\Enums\Currency;
use App\Enums\EquipmentTargetType;
use App\Enums\EquipmentType;
use App\Enums\Rarity;
use App\Enums\ShopItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', array_column(EquipmentType::cases(), 'value'));
            $table->enum('target_type', array_column(EquipmentTargetType::cases(), 'value'));
            $table->enum('rarity', array_column(Rarity::cases(), 'value'))->default(Rarity::Common->value);
            $table->json('stat_bonus')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('user_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->string('equipped_to_type')->nullable();
            $table->unsignedBigInteger('equipped_to_id')->nullable();
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'equipment_id']);
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->enum('item_type', array_column(ShopItemType::cases(), 'value'));
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('price');
            $table->enum('currency', array_column(Currency::cases(), 'value'))->default(Currency::Coins->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_items');
        Schema::dropIfExists('user_equipment');
        Schema::dropIfExists('equipment');
    }
};
