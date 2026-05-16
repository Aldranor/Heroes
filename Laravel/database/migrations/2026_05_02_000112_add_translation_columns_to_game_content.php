<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_domains', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('learning_categories', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->json('question_text_translations')->nullable()->after('question_text');
            $table->json('explanation_translations')->nullable()->after('explanation');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->json('answer_text_translations')->nullable()->after('answer_text');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('creatures', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
            $table->json('visual_theme_translations')->nullable()->after('visual_theme');
            $table->json('personality_translations')->nullable()->after('personality');
        });

        Schema::table('companions', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('npcs', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
        });

        Schema::table('scenario_steps', function (Blueprint $table) {
            $table->json('speaker_name_translations')->nullable()->after('speaker_name');
            $table->json('text_translations')->nullable()->after('text');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('enemies', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });

        $this->backfillTranslations('learning_domains', ['name', 'description']);
        $this->backfillTranslations('learning_categories', ['name', 'description']);
        $this->backfillTranslations('questions', ['question_text', 'explanation']);
        $this->backfillTranslations('answers', ['answer_text']);
        $this->backfillTranslations('skills', ['name', 'description']);
        $this->backfillTranslations('creatures', ['name', 'description', 'visual_theme', 'personality']);
        $this->backfillTranslations('companions', ['name', 'description']);
        $this->backfillTranslations('avatar_items', ['name']);
        $this->backfillTranslations('equipment', ['name', 'description']);
        $this->backfillTranslations('quests', ['title', 'description']);
        $this->backfillTranslations('npcs', ['name', 'description']);
        $this->backfillTranslations('scenarios', ['title']);
        $this->backfillTranslations('scenario_steps', ['speaker_name', 'text']);
        $this->backfillTranslations('zones', ['name', 'description']);
        $this->backfillTranslations('enemies', ['name', 'description']);
        $this->backfillTranslations('levels', ['name']);
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn(['name_translations']);
        });

        Schema::table('enemies', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('scenario_steps', function (Blueprint $table) {
            $table->dropColumn(['speaker_name_translations', 'text_translations']);
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn(['title_translations']);
        });

        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropColumn(['name_translations']);
        });

        Schema::table('companions', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('creatures', function (Blueprint $table) {
            $table->dropColumn([
                'name_translations',
                'description_translations',
                'visual_theme_translations',
                'personality_translations',
            ]);
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text_translations']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_text_translations', 'explanation_translations']);
        });

        Schema::table('learning_categories', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('learning_domains', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });
    }

    private function backfillTranslations(string $table, array $attributes): void
    {
        $records = DB::table($table)
            ->select(array_merge(['id'], $attributes))
            ->get();

        foreach ($records as $record) {
            $translations = [];

            foreach ($attributes as $attribute) {
                if ($record->{$attribute} === null) {
                    continue;
                }

                $translations[$attribute.'_translations'] = json_encode(
                    ['fr' => $record->{$attribute}],
                    JSON_UNESCAPED_UNICODE,
                );
            }

            if ($translations === []) {
                continue;
            }

            DB::table($table)
                ->where('id', $record->id)
                ->update($translations);
        }
    }
};
