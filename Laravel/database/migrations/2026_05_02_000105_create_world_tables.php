<?php

use App\Enums\EnemyType;
use App\Enums\LevelProgressStatus;
use App\Enums\ScenarioActionType;
use App\Enums\ScenarioTriggerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role');
            $table->string('portrait')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('trigger_type', array_column(ScenarioTriggerType::cases(), 'value'));
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scenario_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->foreignId('npc_id')->nullable()->constrained()->nullOnDelete();
            $table->string('speaker_name')->nullable();
            $table->text('text');
            $table->string('portrait')->nullable();
            $table->enum('action_type', array_column(ScenarioActionType::cases(), 'value'))->nullable();
            $table->json('action_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('required_user_level')->default(1);
            $table->string('image')->nullable();
            $table->foreignId('scenario_intro_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('enemies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', array_column(EnemyType::cases(), 'value'))->default(EnemyType::Monster->value);
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedSmallInteger('hp');
            $table->unsignedSmallInteger('attack');
            $table->unsignedSmallInteger('defense');
            $table->unsignedSmallInteger('speed');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_boss')->default(false);
            $table->json('skill_set')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('level_number');
            $table->foreignId('enemy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('required_user_level')->default(1);
            $table->unsignedTinyInteger('required_creature_level')->nullable();
            $table->boolean('is_boss_level')->default(false);
            $table->foreignId('scenario_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->foreignId('unlocks_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->timestamps();

            $table->unique(['zone_id', 'level_number']);
        });

        Schema::create('user_level_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(LevelProgressStatus::cases(), 'value'))
                ->default(LevelProgressStatus::Locked->value);
            $table->json('best_result')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_level_progress');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('enemies');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('scenario_steps');
        Schema::dropIfExists('scenarios');
        Schema::dropIfExists('npcs');
    }
};
