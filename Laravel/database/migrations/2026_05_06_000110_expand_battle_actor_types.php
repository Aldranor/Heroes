<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE battle_turns MODIFY actor_type ENUM('hero', 'user_companion', 'user_creature', 'enemy') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE battle_turns MODIFY actor_type ENUM('user_creature', 'enemy') NOT NULL");
        }
    }
};
