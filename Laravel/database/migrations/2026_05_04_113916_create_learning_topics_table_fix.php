<?php

use App\Enums\QuestionDifficulty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('learning_topics_table_fix');
        $lessonsHadLearningTopicId = Schema::hasTable('lessons') && Schema::hasColumn('lessons', 'learning_topic_id');
        $lessonsHadDifficulty = Schema::hasTable('lessons') && Schema::hasColumn('lessons', 'difficulty');

        if (!Schema::hasTable('learning_topics')) {
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
        }

        if (Schema::hasTable('lessons')) {
            if (!$lessonsHadLearningTopicId || !$lessonsHadDifficulty) {
                Schema::table('lessons', function (Blueprint $table) use ($lessonsHadLearningTopicId, $lessonsHadDifficulty) {
                    if (!$lessonsHadLearningTopicId) {
                        $table->foreignId('learning_topic_id')
                            ->nullable()
                            ->constrained('learning_topics')
                            ->cascadeOnDelete();
                    }

                    if (!$lessonsHadDifficulty) {
                        $table->string('difficulty')->default(QuestionDifficulty::Easy->value);
                    }
                });
            }
        }

        $this->backfillTopicsAndLessons(
            forceLearningTopicBackfill: !$lessonsHadLearningTopicId,
            forceDifficultyBackfill: !$lessonsHadDifficulty,
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('lessons')) {
            $hasLearningTopicId = Schema::hasColumn('lessons', 'learning_topic_id');
            $hasDifficulty = Schema::hasColumn('lessons', 'difficulty');

            if ($hasLearningTopicId || $hasDifficulty) {
                Schema::table('lessons', function (Blueprint $table) use ($hasLearningTopicId, $hasDifficulty) {
                    if ($hasLearningTopicId) {
                        $table->dropForeign(['learning_topic_id']);
                        $table->dropColumn('learning_topic_id');
                    }

                    if ($hasDifficulty) {
                        $table->dropColumn('difficulty');
                    }
                });
            }
        }

        Schema::dropIfExists('learning_topics');
    }

    private function backfillTopicsAndLessons(bool $forceLearningTopicBackfill, bool $forceDifficultyBackfill): void
    {
        if (!Schema::hasTable('learning_topics') || !Schema::hasTable('learning_categories') || !Schema::hasTable('lessons')) {
            return;
        }

        $now = now();
        $topicIdsByCategoryId = [];

        $categories = DB::table('learning_categories')
            ->select([
                'id',
                'learning_domain_id',
                'name',
                'name_translations',
                'slug',
                'description',
                'description_translations',
                'icon',
                'color',
                'is_mixed',
                'sort_order',
            ])
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            if ((bool) $category->is_mixed) {
                continue;
            }

            $existingTopic = DB::table('learning_topics')
                ->where('learning_domain_id', $category->learning_domain_id)
                ->where('slug', $category->slug)
                ->first(['id']);

            $payload = [
                'title' => $category->name,
                'title_translations' => $category->name_translations,
                'description' => $category->description,
                'description_translations' => $category->description_translations,
                'icon' => $category->icon,
                'color' => $category->color,
                'sort_order' => $category->sort_order,
                'updated_at' => $now,
            ];

            if ($existingTopic) {
                DB::table('learning_topics')
                    ->where('id', $existingTopic->id)
                    ->update($payload);

                $topicIdsByCategoryId[$category->id] = $existingTopic->id;
                continue;
            }

            $topicIdsByCategoryId[$category->id] = DB::table('learning_topics')->insertGetId([
                'learning_domain_id' => $category->learning_domain_id,
                'title' => $category->name,
                'title_translations' => $category->name_translations,
                'slug' => $category->slug,
                'description' => $category->description,
                'description_translations' => $category->description_translations,
                'icon' => $category->icon,
                'color' => $category->color,
                'sort_order' => $category->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $lessons = DB::table('lessons')
            ->select(['id', 'learning_category_id', 'learning_topic_id', 'difficulty'])
            ->orderBy('id')
            ->get();

        $categorySlugs = $categories->pluck('slug', 'id');

        foreach ($lessons as $lesson) {
            $updates = [];

            if ($lesson->learning_category_id !== null && ($forceLearningTopicBackfill || $lesson->learning_topic_id === null)) {
                $topicId = $topicIdsByCategoryId[$lesson->learning_category_id] ?? null;

                if ($topicId !== null) {
                    $updates['learning_topic_id'] = $topicId;
                }
            }

            if ($forceDifficultyBackfill || blank($lesson->difficulty)) {
                $updates['difficulty'] = match ($categorySlugs[$lesson->learning_category_id] ?? null) {
                    'priority', 'speed' => QuestionDifficulty::Medium->value,
                    'dangers' => QuestionDifficulty::Hard->value,
                    default => QuestionDifficulty::Easy->value,
                };
            }

            if ($updates !== []) {
                DB::table('lessons')->where('id', $lesson->id)->update($updates);
            }
        }
    }
};
