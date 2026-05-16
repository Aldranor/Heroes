<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->string('style')->nullable()->after('nickname');
            $table->json('colors')->nullable()->after('style');
        });

        DB::table('avatars')
            ->select(['id', 'base_style', 'hair_color', 'skin_color', 'outfit'])
            ->orderBy('id')
            ->get()
            ->each(function (object $avatar): void {
                DB::table('avatars')
                    ->where('id', $avatar->id)
                    ->update([
                        'style' => $avatar->base_style,
                        'colors' => json_encode([
                            'hair' => $avatar->hair_color,
                            'skin' => $avatar->skin_color,
                            'accent' => $avatar->outfit,
                        ]),
                    ]);
            });

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropColumn(['base_style', 'hair_color', 'skin_color', 'outfit']);
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->string('style')->nullable(false)->change();
            $table->json('colors')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->string('base_style')->nullable()->after('nickname');
            $table->string('hair_color')->nullable()->after('base_style');
            $table->string('skin_color')->nullable()->after('hair_color');
            $table->string('outfit')->nullable()->after('skin_color');
        });

        DB::table('avatars')
            ->select(['id', 'style', 'colors'])
            ->orderBy('id')
            ->get()
            ->each(function (object $avatar): void {
                $colors = json_decode($avatar->colors ?: '[]', true);

                DB::table('avatars')
                    ->where('id', $avatar->id)
                    ->update([
                        'base_style' => $avatar->style,
                        'hair_color' => $colors['hair'] ?? '#4B5563',
                        'skin_color' => $colors['skin'] ?? '#F3D1B0',
                        'outfit' => $colors['accent'] ?? '#E07A5F',
                    ]);
            });

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropColumn(['style', 'colors']);
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->string('base_style')->nullable(false)->change();
            $table->string('hair_color')->nullable(false)->change();
            $table->string('skin_color')->nullable(false)->change();
            $table->string('outfit')->nullable(false)->change();
        });
    }
};
