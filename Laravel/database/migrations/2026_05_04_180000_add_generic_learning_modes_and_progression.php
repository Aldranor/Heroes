<?php

use App\Enums\MasteryStatus;
use App\Enums\QuestionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('type')->default(QuestionType::Definition->value)->after('learning_category_id');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->string('image')->nullable()->after('answer_text_translations');
            $table->json('metadata')->nullable()->after('image');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('question_ids');
        });

        Schema::table('training_answers', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('awarded_companion_xp');
        });

        Schema::create('user_learning_item_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('learning_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type');
            $table->string('item_key');
            $table->string('mastery_status')->default(MasteryStatus::Unknown->value);
            $table->unsignedSmallInteger('mastery_score')->default(0);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('correct_attempts')->default(0);
            $table->unsignedSmallInteger('error_count')->default(0);
            $table->unsignedSmallInteger('review_count')->default(0);
            $table->unsignedSmallInteger('difficulty_weight')->default(0);
            $table->unsignedInteger('average_response_seconds')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'item_type', 'item_key'], 'learning_progress_unique_item');
            $table->index(['learning_category_id', 'item_type', 'mastery_status'], 'learning_progress_category_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_learning_item_progress');

        Schema::table('training_answers', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn(['image', 'metadata']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
