<?php

namespace Database\Seeders\RoadSpirits;

use Illuminate\Database\Seeder;

class RoadSpiritsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoadSpiritsLearningSeeder::class,
            RoadSpiritsLessonSeeder::class,
            RoadSpiritsRpgSeeder::class,
            RoadSpiritsCompanionSeeder::class,
            RoadSpiritsSkillTreeSeeder::class,
            RoadSpiritsWorldSeeder::class,
            RoadSpiritsCommerceSeeder::class,
            RoadSpiritsQuestSeeder::class,
            RoadSpiritsPlayerSeeder::class,
        ]);
    }
}
