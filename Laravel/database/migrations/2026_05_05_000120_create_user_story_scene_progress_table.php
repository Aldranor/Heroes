<?php

use App\Enums\StorySceneType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_story_scene_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('scene_type', array_column(StorySceneType::cases(), 'value'));
            $table->unsignedBigInteger('scene_id');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scene_type', 'scene_id'], 'user_story_scene_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_story_scene_progress');
    }
};
