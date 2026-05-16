<?php

use App\Enums\AdventureDialogueTrigger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventure_dialogues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adventure_node_id')->nullable()->constrained('adventure_nodes')->nullOnDelete();
            $table->string('title');
            $table->string('trigger_type')->default(AdventureDialogueTrigger::MapStarted->value);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('script')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure_dialogues');
    }
};
