<?php

use App\Enums\TrainingSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_creature_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', array_column(TrainingSessionStatus::cases(), 'value'))
                ->default(TrainingSessionStatus::Pending->value);
            $table->json('question_ids')->nullable();
            $table->unsignedTinyInteger('total_questions')->default(5);
            $table->unsignedTinyInteger('correct_answers')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->boolean('perfect_session')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('training_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('time_spent_seconds')->nullable();
            $table->unsignedSmallInteger('awarded_user_xp')->default(0);
            $table->unsignedSmallInteger('awarded_creature_xp')->default(0);
            $table->timestamps();

            $table->unique(['training_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_answers');
        Schema::dropIfExists('training_sessions');
    }
};
