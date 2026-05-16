<?php

use App\Enums\AvatarItemType;
use App\Enums\Rarity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nickname');
            $table->string('base_style');
            $table->string('hair_color');
            $table->string('skin_color');
            $table->string('outfit');
            $table->json('equipped_items')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('avatar_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', array_column(AvatarItemType::cases(), 'value'));
            $table->enum('rarity', array_column(Rarity::cases(), 'value'))->default(Rarity::Common->value);
            $table->unsignedInteger('price')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_cosmetic_only')->default(true);
            $table->timestamps();
        });

        Schema::create('user_avatar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('avatar_item_id')->constrained()->cascadeOnDelete();
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'avatar_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_avatar_items');
        Schema::dropIfExists('avatar_items');
        Schema::dropIfExists('avatars');
    }
};
