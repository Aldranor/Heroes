<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companions', function (Blueprint $table) {
            $table->string('sprite_path')->nullable()->after('acquisition_method');
        });
    }

    public function down(): void
    {
        Schema::table('companions', function (Blueprint $table) {
            $table->dropColumn('sprite_path');
        });
    }
};
