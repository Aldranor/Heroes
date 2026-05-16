<?php

use App\Enums\BattleActorType;
use App\Enums\BattleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enemy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', array_column(BattleStatus::cases(), 'value'))
                ->default(BattleStatus::Ongoing->value);
            $table->json('party')->nullable();
            $table->json('state')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('reward_claimed')->default(false);
            $table->timestamps();
        });

        Schema::create('battle_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('turn_number');
            $table->enum('actor_type', array_column(BattleActorType::cases(), 'value'));
            $table->unsignedBigInteger('actor_id');
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('damage')->default(0);
            $table->unsignedSmallInteger('healing')->default(0);
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_turns');
        Schema::dropIfExists('battles');
    }
};
