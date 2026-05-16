<?php

use App\Enums\QuestObjectiveType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->modifyQuestObjectiveEnum(array_column(QuestObjectiveType::cases(), 'value'));
    }

    public function down(): void
    {
        $this->modifyQuestObjectiveEnum([
            'complete_training_sessions',
            'answer_correct_questions',
            'defeat_enemies',
            'defeat_boss',
            'reach_creature_level',
            'complete_level',
            'earn_coins',
        ]);
    }

    private function modifyQuestObjectiveEnum(array $values): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $enumValues = collect($values)
            ->map(fn (string $value): string => "'".str_replace("'", "''", $value)."'")
            ->implode(',');

        DB::statement("ALTER TABLE quests MODIFY objective_type ENUM({$enumValues}) NOT NULL");
    }
};
