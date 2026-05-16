<?php

use App\Enums\ArenaRunStatus;
use App\Enums\BattleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arena_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(ArenaRunStatus::cases(), 'value'))
                ->default(ArenaRunStatus::Ongoing->value);
            $table->unsignedTinyInteger('current_stage')->default(1);
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->json('enemy_ids')->nullable();
            $table->json('state')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('arena_battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arena_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('battle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enemy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->enum('status', array_column(BattleStatus::cases(), 'value'))
                ->default(BattleStatus::Ongoing->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_battles');
        Schema::dropIfExists('arena_runs');
    }
};
