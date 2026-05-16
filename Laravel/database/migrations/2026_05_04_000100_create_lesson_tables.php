<?php

use App\Enums\FlashcardProgressStatus;
use App\Enums\LessonProgressStatus;
use App\Enums\LessonSectionType;
use App\Enums\LessonStatus;
use App\Enums\QuestionDifficulty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['learning_domain_id', 'slug']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->json('summary_translations')->nullable();
            $table->string('mentor_name')->nullable();
            $table->string('mentor_title')->nullable();
            $table->string('hero_image')->nullable();
            $table->enum('status', array_column(LessonStatus::cases(), 'value'))
                ->default(LessonStatus::Draft->value);
            $table->enum('difficulty', array_column(QuestionDifficulty::cases(), 'value'))
                ->default(QuestionDifficulty::Easy->value);
            $table->unsignedSmallInteger('academic_xp_reward')->default(25);
            $table->unsignedTinyInteger('quiz_pass_score')->default(100);
            $table->boolean('unlocks_training')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->enum('type', array_column(LessonSectionType::cases(), 'value'));
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->longText('body');
            $table->json('body_translations')->nullable();
            $table->string('image')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->text('front');
            $table->json('front_translations')->nullable();
            $table->text('back');
            $table->json('back_translations')->nullable();
            $table->string('visual_label')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['lesson_id', 'question_id']);
        });

        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(LessonProgressStatus::cases(), 'value'))
                ->default(LessonProgressStatus::NotStarted->value);
            $table->unsignedTinyInteger('quiz_score')->default(0);
            $table->unsignedTinyInteger('quiz_total')->default(0);
            $table->boolean('quiz_passed')->default(false);
            $table->unsignedSmallInteger('academic_xp_awarded')->default(0);
            $table->json('quiz_answers')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('training_unlocked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('user_flashcard_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flashcard_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(FlashcardProgressStatus::cases(), 'value'))
                ->default(FlashcardProgressStatus::Viewed->value);
            $table->unsignedSmallInteger('flipped_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'flashcard_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_flashcard_progress');
        Schema::dropIfExists('user_lesson_progress');
        Schema::dropIfExists('lesson_question');
        Schema::dropIfExists('flashcards');
        Schema::dropIfExists('lesson_sections');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('learning_topics');
    }
};
