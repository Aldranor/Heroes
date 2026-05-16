<?php

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_domains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('locale')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_mixed')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['learning_domain_id', 'slug']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_category_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->string('image')->nullable();
            $table->enum('difficulty', array_column(QuestionDifficulty::cases(), 'value'))
                ->default(QuestionDifficulty::Easy->value);
            $table->enum('status', array_column(QuestionStatus::cases(), 'value'))
                ->default(QuestionStatus::Draft->value);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('answer_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('learning_categories');
        Schema::dropIfExists('learning_domains');
    }
};
