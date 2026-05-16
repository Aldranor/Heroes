<?php

use App\Enums\Rarity;
use App\Enums\SkillTarget;
use App\Enums\SkillType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('skill_points')->default(0)->after('level');
            $table->unsignedInteger('special_skill_points')->default(0)->after('skill_points');
        });

        DB::table('users')
            ->select(['id', 'level'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'skill_points' => max(0, ((int) $user->level) - 1),
                        ]);
                }
            });

        Schema::table('skills', function (Blueprint $table) {
            $table->enum('type', array_column(SkillType::cases(), 'value'))->change();
            $table->enum('target', array_column(SkillTarget::cases(), 'value'))->change();
            $table->foreignId('learning_category_id')->nullable()->after('target')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('energy_cost')->default(0)->after('power');
            $table->unsignedTinyInteger('required_level')->default(1)->after('unlock_level');
            $table->boolean('is_passive')->default(false)->after('required_level');
            $table->string('icon')->nullable()->after('animation_key');
            $table->enum('rarity', array_column(Rarity::cases(), 'value'))->nullable()->after('icon');
            $table->json('combat_effect')->nullable()->after('rarity');
            $table->json('prerequisites')->nullable()->after('combat_effect');
        });

        DB::table('skills')
            ->select(['id', 'unlock_level'])
            ->orderBy('id')
            ->chunkById(100, function ($skills): void {
                foreach ($skills as $skill) {
                    DB::table('skills')
                        ->where('id', $skill->id)
                        ->update([
                            'required_level' => (int) $skill->unlock_level,
                        ]);
                }
            });

        Schema::create('skill_trees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('column_index')->default(1);
            $table->unsignedSmallInteger('row_index')->default(1);
            $table->unsignedTinyInteger('unlock_cost')->default(1);
            $table->unsignedTinyInteger('special_cost')->default(0);
            $table->unsignedTinyInteger('required_level')->default(1);
            $table->json('prerequisite_node_ids')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['skill_tree_id', 'skill_id']);
        });

        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('skill_tree');
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill_id']);
        });

        Schema::create('user_skill_tree_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_node_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('spent_skill_points')->default(0);
            $table->unsignedTinyInteger('spent_special_points')->default(0);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill_node_id']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedInteger('reward_skill_points')->default(0)->after('reward_coins');
            $table->unsignedInteger('reward_special_skill_points')->default(0)->after('reward_skill_points');
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['reward_skill_points', 'reward_special_skill_points']);
        });

        Schema::dropIfExists('user_skill_tree_progress');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skill_nodes');
        Schema::dropIfExists('skill_trees');

        Schema::table('skills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_category_id');
            $table->dropColumn([
                'energy_cost',
                'required_level',
                'is_passive',
                'icon',
                'rarity',
                'combat_effect',
                'prerequisites',
            ]);
            $table->enum('type', [
                SkillType::Attack->value,
                SkillType::Defense->value,
                SkillType::Heal->value,
                SkillType::Buff->value,
                SkillType::Debuff->value,
            ])->change();
            $table->enum('target', [
                SkillTarget::Enemy->value,
                SkillTarget::Self->value,
                SkillTarget::Ally->value,
            ])->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['skill_points', 'special_skill_points']);
        });
    }
};
