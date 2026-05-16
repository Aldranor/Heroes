<?php

use App\Enums\CompanionAcquisitionMethod;
use App\Enums\CompanionRole;
use App\Enums\Rarity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('specialization_category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->enum('role', array_column(CompanionRole::cases(), 'value'));
            $table->enum('rarity', array_column(Rarity::cases(), 'value'))->default(Rarity::Common->value);
            $table->unsignedSmallInteger('base_hp');
            $table->unsignedSmallInteger('base_attack');
            $table->unsignedSmallInteger('base_defense');
            $table->unsignedSmallInteger('base_speed');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->enum('acquisition_method', array_column(CompanionAcquisitionMethod::cases(), 'value'))
                ->default(CompanionAcquisitionMethod::Starter->value);
            $table->timestamps();
        });

        Schema::create('companion_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['companion_id', 'skill_id']);
        });

        Schema::create('user_companions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('companion_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('current_hp');
            $table->string('nickname')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'companion_id']);
        });

        Schema::create('user_companion_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_companion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_companion_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_companion_skills');
        Schema::dropIfExists('user_companions');
        Schema::dropIfExists('companion_skill');
        Schema::dropIfExists('companions');
    }
};
