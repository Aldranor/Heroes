<?php

use App\Enums\AdventureNodeProgressStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('theme')->nullable();
            $table->string('difficulty')->nullable();
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('intro_scenario_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('adventure_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('intro_scenario_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('adventure_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->string('node_type');
            $table->string('training_mode')->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_start')->default(false);
            $table->boolean('is_repeatable')->default(true);
            $table->decimal('position_x', 5, 2)->nullable();
            $table->decimal('position_y', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('adventure_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_adventure_node_id')->constrained('adventure_nodes')->cascadeOnDelete();
            $table->foreignId('to_adventure_node_id')->constrained('adventure_nodes')->cascadeOnDelete();
            $table->string('path_type')->default('road');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('user_adventure_node_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adventure_node_id')->constrained('adventure_nodes')->cascadeOnDelete();
            $table->string('status')->default(AdventureNodeProgressStatus::Locked->value);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'adventure_node_id']);
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->foreignId('adventure_node_id')
                ->nullable()
                ->after('user_companion_id')
                ->constrained('adventure_nodes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adventure_node_id');
        });

        Schema::dropIfExists('user_adventure_node_progress');
        Schema::dropIfExists('adventure_paths');
        Schema::dropIfExists('adventure_nodes');
        Schema::dropIfExists('adventure_maps');
        Schema::dropIfExists('worlds');
    }
};
