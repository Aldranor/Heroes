<?php

use App\Enums\QuestObjectiveType;
use App\Enums\QuestStatus;
use App\Enums\QuestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('npc_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('type', array_column(QuestType::cases(), 'value'));
            $table->enum('objective_type', array_column(QuestObjectiveType::cases(), 'value'));
            $table->unsignedInteger('objective_target');
            $table->json('objective_payload')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->foreignId('reward_equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('reward_avatar_item_id')->nullable()->constrained('avatar_items')->nullOnDelete();
            $table->foreignId('reward_creature_id')->nullable()->constrained('creatures')->nullOnDelete();
            $table->boolean('is_repeatable')->default(false);
            $table->boolean('is_daily')->default(false);
            $table->unsignedTinyInteger('unlock_level')->default(1);
            $table->foreignId('scenario_intro_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->foreignId('scenario_complete_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('user_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(QuestStatus::cases(), 'value'))
                ->default(QuestStatus::Available->value);
            $table->unsignedInteger('progress')->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quests');
        Schema::dropIfExists('quests');
    }
};
