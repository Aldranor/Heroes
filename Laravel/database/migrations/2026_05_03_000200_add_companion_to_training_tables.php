<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->foreignId('user_companion_id')
                ->nullable()
                ->after('learning_category_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('training_answers', function (Blueprint $table) {
            $table->unsignedSmallInteger('awarded_companion_xp')
                ->default(0)
                ->after('awarded_creature_xp');
        });
    }

    public function down(): void
    {
        Schema::table('training_answers', function (Blueprint $table) {
            $table->dropColumn('awarded_companion_xp');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_companion_id');
        });
    }
};
