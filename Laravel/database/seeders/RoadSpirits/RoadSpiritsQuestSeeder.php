<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\QuestObjectiveType;
use App\Enums\QuestType;
use App\Models\Avatar\AvatarItem;
use App\Models\Commerce\Equipment;
use App\Models\Creatures\Creature;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Lessons\Lesson;
use App\Models\Quests\Quest;
use App\Models\World\Enemy;
use App\Models\World\Npc;
use App\Models\World\Scenario;
use App\Models\World\Zone;
use Illuminate\Database\Seeder;

class RoadSpiritsQuestSeeder extends Seeder
{
    public function run(): void
    {
        $domain = LearningDomain::query()->where('slug', 'driving_license_be')->firstOrFail();
        $categories = LearningCategory::query()->where('learning_domain_id', $domain->id)->get()->keyBy('slug');
        $npcs = Npc::query()->whereIn('slug', ['instructeur', 'marchande'])->get()->keyBy('slug');
        $scenarios = Scenario::query()->whereIn('slug', [
            'road-spirits-intro',
            'zone-1-boss-end',
        ])->get()->keyBy('slug');
        $zone = Zone::query()->where('slug', 'ville-debutante')->firstOrFail();
        $boss = Enemy::query()->where('slug', 'chauffard-du-carrefour')->firstOrFail();
        $starterEquipment = Equipment::query()->where('slug', 'veste-reflechissante')->firstOrFail();
        $rareAvatarItem = AvatarItem::query()->where('slug', 'foulard-ambre')->firstOrFail();
        $rewardCreature = Creature::query()->where('slug', 'chat-reflexe')->firstOrFail();
        $signsLesson = Lesson::query()->where('slug', 'academie-heros-signs')->first();

        $quests = [
            [
                'title' => 'Premier module',
                'slug' => 'premier-cours',
                'description' => 'Valide un module de la bibliothèque pour débloquer ta première mission.',
                'type' => QuestType::Training,
                'objective_type' => QuestObjectiveType::CompleteLessons,
                'objective_target' => 1,
                'objective_payload' => array_filter(['lesson_id' => $signsLesson?->id]),
                'reward_xp' => 30,
                'reward_coins' => 40,
                'reward_skill_points' => 1,
                'npc_id' => $npcs['instructeur']->id,
            ],
            [
                'title' => 'Premiers panneaux',
                'slug' => 'premiers-panneaux',
                'description' => 'Réussis 10 bonnes réponses sur la signalisation pour stabiliser les rues de la ville.',
                'type' => QuestType::Training,
                'objective_type' => QuestObjectiveType::AnswerCorrectQuestions,
                'objective_target' => 10,
                'objective_payload' => ['learning_category_id' => $categories['signs']->id],
                'reward_xp' => 40,
                'reward_coins' => 55,
                'npc_id' => $npcs['instructeur']->id,
                'scenario_intro_id' => $scenarios['road-spirits-intro']->id,
            ],
            [
                'title' => 'Nettoyer le carrefour',
                'slug' => 'nettoyer-le-carrefour',
                'description' => 'Bats 3 ennemis de la Ville débutante pour rendre les rues praticables.',
                'type' => QuestType::Battle,
                'objective_type' => QuestObjectiveType::DefeatEnemies,
                'objective_target' => 3,
                'objective_payload' => ['zone_id' => $zone->id],
                'reward_xp' => 45,
                'reward_coins' => 65,
                'reward_equipment_id' => $starterEquipment->id,
                'npc_id' => $npcs['instructeur']->id,
            ],
            [
                'title' => 'Révision vitesse',
                'slug' => 'revision-vitesse',
                'description' => "Termine 2 sessions d'entraînement sur la vitesse pour préparer la suite de la map.",
                'type' => QuestType::Training,
                'objective_type' => QuestObjectiveType::CompleteTrainingSessions,
                'objective_target' => 2,
                'objective_payload' => ['learning_category_id' => $categories['speed']->id],
                'reward_xp' => 35,
                'reward_coins' => 50,
                'npc_id' => $npcs['instructeur']->id,
            ],
            [
                'title' => 'Premier style de route',
                'slug' => 'premier-style-de-route',
                'description' => 'Accumule 100 pièces pour montrer que tu maîtrises le rythme de la ville.',
                'type' => QuestType::Story,
                'objective_type' => QuestObjectiveType::EarnCoins,
                'objective_target' => 100,
                'reward_xp' => 25,
                'reward_coins' => 35,
                'reward_avatar_item_id' => $rareAvatarItem->id,
                'npc_id' => $npcs['marchande']->id,
            ],
            [
                'title' => 'Le boss de la ville',
                'slug' => 'le-boss-de-la-ville',
                'description' => 'Vaincs le Chauffard du Carrefour pour libérer la zone.',
                'type' => QuestType::Boss,
                'objective_type' => QuestObjectiveType::DefeatBoss,
                'objective_target' => 1,
                'objective_payload' => ['enemy_id' => $boss->id],
                'reward_xp' => 90,
                'reward_coins' => 120,
                'reward_special_skill_points' => 1,
                'reward_creature_id' => $rewardCreature->id,
                'npc_id' => $npcs['instructeur']->id,
                'scenario_complete_id' => $scenarios['zone-1-boss-end']->id,
            ],
        ];

        foreach ($quests as $questData) {
            Quest::query()->updateOrCreate(
                ['slug' => $questData['slug']],
                array_merge($questData, [
                    'title_translations' => ['fr' => $questData['title']],
                    'description_translations' => ['fr' => $questData['description']],
                    'learning_domain_id' => $domain->id,
                    'is_repeatable' => false,
                    'is_daily' => false,
                    'unlock_level' => 1,
                ]),
            );
        }
    }
}
