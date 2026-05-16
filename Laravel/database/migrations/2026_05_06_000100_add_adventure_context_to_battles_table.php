<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            $table->foreignId('adventure_node_id')
                ->nullable()
                ->after('level_id')
                ->constrained('adventure_nodes')
                ->nullOnDelete();
            $table->json('metadata')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adventure_node_id');
            $table->dropColumn('metadata');
        });
    }
};
