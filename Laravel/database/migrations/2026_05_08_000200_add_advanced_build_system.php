<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'hero_class')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('hero_class')->default('strategist')->after('level');
                $table->string('combat_role')->default('hybrid')->after('hero_class');
                $table->string('specialization_slug')->default('tactician')->after('combat_role');
                $table->json('combat_loadout')->nullable()->after('specialization_slug');
                $table->unsignedTinyInteger('combat_position')->default(1)->after('combat_loadout');
                $table->unsignedInteger('free_respecs')->default(1)->after('special_skill_points');
            });
        }

        if (! Schema::hasColumn('companions', 'combat_class')) {
            Schema::table('companions', function (Blueprint $table) {
                $table->string('combat_class')->nullable()->after('role');
                $table->string('build_role')->nullable()->after('combat_class');
                $table->string('specialization_slug')->nullable()->after('build_role');
                $table->foreignId('unique_passive_skill_id')->nullable()->after('specialization_slug')->constrained('skills')->nullOnDelete();
                $table->foreignId('signature_skill_id')->nullable()->after('unique_passive_skill_id')->constrained('skills')->nullOnDelete();
                $table->json('build_tags')->nullable()->after('signature_skill_id');
            });
        }

        if (! Schema::hasColumn('user_companions', 'build_role')) {
            Schema::table('user_companions', function (Blueprint $table) {
                $table->string('build_role')->nullable()->after('level');
                $table->string('specialization_slug')->nullable()->after('build_role');
                $table->json('combat_loadout')->nullable()->after('specialization_slug');
                $table->unsignedTinyInteger('combat_position')->default(2)->after('combat_loadout');
                $table->unsignedInteger('talent_points')->default(0)->after('xp');
                $table->unsignedInteger('special_talent_points')->default(0)->after('talent_points');
                $table->unsignedInteger('free_respecs')->default(1)->after('special_talent_points');
            });

            DB::table('user_companions')
                ->select(['id', 'level'])
                ->orderBy('id')
                ->chunkById(100, function ($companions): void {
                    foreach ($companions as $companion) {
                        DB::table('user_companions')
                            ->where('id', $companion->id)
                            ->update([
                                'talent_points' => max(0, ((int) $companion->level) - 1),
                            ]);
                    }
                });
        }

        if (! Schema::hasColumn('skills', 'slot_type')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->string('slot_type')->default('active')->after('target');
                $table->string('resource_type')->default('energy')->after('energy_cost');
                $table->string('range_type')->nullable()->after('resource_type');
                $table->string('damage_family')->nullable()->after('range_type');
                $table->json('scaling')->nullable()->after('damage_family');
                $table->json('tags')->nullable()->after('scaling');
                $table->json('specialization_keys')->nullable()->after('tags');
                $table->json('role_keys')->nullable()->after('specialization_keys');
            });
        }

        if (! Schema::hasColumn('skill_trees', 'subject_type')) {
            Schema::table('skill_trees', function (Blueprint $table) {
                $table->string('subject_type')->default('hero')->after('slug');
                $table->string('subject_key')->nullable()->after('subject_type');
                $table->string('class_slug')->nullable()->after('subject_key');
                $table->string('role_slug')->nullable()->after('class_slug');
                $table->string('specialization_slug')->nullable()->after('role_slug');
            });
        }

        if (! Schema::hasColumn('user_skills', 'owner_type')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->string('owner_type')->default('hero')->after('user_id');
                $table->unsignedBigInteger('owner_reference_id')->default(0)->after('owner_type');
                $table->boolean('is_equipped')->default(false)->after('source');
            });
        }

        if (! $this->hasIndex('user_skills', 'user_skills_owner_unique')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->unique(['user_id', 'owner_type', 'owner_reference_id', 'skill_id'], 'user_skills_owner_unique');
            });
        }

        if ($this->hasIndex('user_skills', 'user_skills_user_id_skill_id_unique')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->dropUnique('user_skills_user_id_skill_id_unique');
            });
        }

        if (! Schema::hasColumn('user_skill_tree_progress', 'owner_type')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->string('owner_type')->default('hero')->after('user_id');
                $table->unsignedBigInteger('owner_reference_id')->default(0)->after('owner_type');
            });
        }

        if (! $this->hasIndex('user_skill_tree_progress', 'user_skill_tree_progress_owner_unique')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->unique(['user_id', 'owner_type', 'owner_reference_id', 'skill_node_id'], 'user_skill_tree_progress_owner_unique');
            });
        }

        if ($this->hasIndex('user_skill_tree_progress', 'user_skill_tree_progress_user_id_skill_node_id_unique')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->dropUnique('user_skill_tree_progress_user_id_skill_node_id_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('user_skill_tree_progress', 'user_skill_tree_progress_owner_unique')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->dropUnique('user_skill_tree_progress_owner_unique');
            });
        }
        if (Schema::hasColumn('user_skill_tree_progress', 'owner_type')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->dropColumn(['owner_type', 'owner_reference_id']);
            });
        }
        if (! $this->hasIndex('user_skill_tree_progress', 'user_skill_tree_progress_user_id_skill_node_id_unique')) {
            Schema::table('user_skill_tree_progress', function (Blueprint $table) {
                $table->unique(['user_id', 'skill_node_id']);
            });
        }

        if ($this->hasIndex('user_skills', 'user_skills_owner_unique')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->dropUnique('user_skills_owner_unique');
            });
        }
        if (Schema::hasColumn('user_skills', 'owner_type')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->dropColumn(['owner_type', 'owner_reference_id', 'is_equipped']);
            });
        }
        if (! $this->hasIndex('user_skills', 'user_skills_user_id_skill_id_unique')) {
            Schema::table('user_skills', function (Blueprint $table) {
                $table->unique(['user_id', 'skill_id']);
            });
        }

        if (Schema::hasColumn('skill_trees', 'subject_type')) {
            Schema::table('skill_trees', function (Blueprint $table) {
                $table->dropColumn(['subject_type', 'subject_key', 'class_slug', 'role_slug', 'specialization_slug']);
            });
        }

        if (Schema::hasColumn('skills', 'slot_type')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn([
                    'slot_type',
                    'resource_type',
                    'range_type',
                    'damage_family',
                    'scaling',
                    'tags',
                    'specialization_keys',
                    'role_keys',
                ]);
            });
        }

        if (Schema::hasColumn('user_companions', 'build_role')) {
            Schema::table('user_companions', function (Blueprint $table) {
                $table->dropColumn([
                    'build_role',
                    'specialization_slug',
                    'combat_loadout',
                    'combat_position',
                    'talent_points',
                    'special_talent_points',
                    'free_respecs',
                ]);
            });
        }

        if (Schema::hasColumn('companions', 'combat_class')) {
            Schema::table('companions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('signature_skill_id');
                $table->dropConstrainedForeignId('unique_passive_skill_id');
                $table->dropColumn(['combat_class', 'build_role', 'specialization_slug', 'build_tags']);
            });
        }

        if (Schema::hasColumn('users', 'hero_class')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'hero_class',
                    'combat_role',
                    'specialization_slug',
                    'combat_loadout',
                    'combat_position',
                    'free_respecs',
                ]);
            });
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'`');

        foreach ($indexes as $row) {
            if (($row->Key_name ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
