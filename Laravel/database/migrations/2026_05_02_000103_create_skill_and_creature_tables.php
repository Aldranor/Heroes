<?php

use App\Enums\CreatureAcquisitionMethod;
use App\Enums\Rarity;
use App\Enums\SkillTarget;
use App\Enums\SkillType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', array_column(SkillType::cases(), 'value'));
            $table->unsignedSmallInteger('power')->default(0);
            $table->unsignedTinyInteger('cooldown')->nullable();
            $table->enum('target', array_column(SkillTarget::cases(), 'value'));
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->string('animation_key')->nullable();
            $table->timestamps();
        });

        Schema::create('creatures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('rarity', array_column(Rarity::cases(), 'value'))->default(Rarity::Common->value);
            $table->unsignedSmallInteger('base_hp');
            $table->unsignedSmallInteger('base_attack');
            $table->unsignedSmallInteger('base_defense');
            $table->unsignedSmallInteger('base_speed');
            $table->text('description')->nullable();
            $table->string('visual_theme')->nullable();
            $table->text('personality')->nullable();
            $table->boolean('starter_allowed')->default(false);
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->enum('acquisition_method', array_column(CreatureAcquisitionMethod::cases(), 'value'))
                ->default(CreatureAcquisitionMethod::Starter->value);
            $table->foreignId('default_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('creature_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['creature_id', 'skill_id']);
        });

        Schema::create('user_creatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creature_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('current_hp')->nullable();
            $table->string('nickname')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'creature_id']);
        });

        Schema::create('user_creature_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_creature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_creature_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_creature_skills');
        Schema::dropIfExists('user_creatures');
        Schema::dropIfExists('creature_skill');
        Schema::dropIfExists('creatures');
        Schema::dropIfExists('skills');
    }
};
