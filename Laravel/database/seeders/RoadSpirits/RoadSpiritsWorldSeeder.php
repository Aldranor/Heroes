<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\EnemyType;
use App\Enums\ScenarioActionType;
use App\Enums\ScenarioTriggerType;
use App\Models\Learning\LearningDomain;
use App\Models\World\Enemy;
use App\Models\World\Level;
use App\Models\World\Npc;
use App\Models\World\Scenario;
use App\Models\World\ScenarioStep;
use App\Models\World\Zone;
use Illuminate\Database\Seeder;

class RoadSpiritsWorldSeeder extends Seeder
{
    public function run(): void
    {
        $domain = LearningDomain::query()->where('slug', 'driving_license_be')->firstOrFail();

        $npcs = collect([
            ['name' => 'Instructeur', 'slug' => 'instructeur', 'role' => 'mentor', 'description' => 'Guide les nouveaux esprits de la route.'],
            ['name' => 'Marchande', 'slug' => 'marchande', 'role' => 'merchant', 'description' => 'Vend équipements et tissus rares.'],
            ['name' => "Gardien de l'arène", 'slug' => 'gardien-de-l-arene', 'role' => 'arena_keeper', 'description' => 'Observe les duels et remet les récompenses.'],
        ])->mapWithKeys(function (array $npcData) {
            $npc = Npc::query()->updateOrCreate(['slug' => $npcData['slug']], array_merge($npcData, [
                'name_translations' => ['fr' => $npcData['name']],
                'description_translations' => ['fr' => $npcData['description']],
            ]));

            return [$npc->slug => $npc];
        });

        $scenarios = [];

        $scenarios['road-spirits-intro'] = $this->seedScenario(
            $domain->id,
            'road-spirits-intro',
            'Réveil des esprits',
            ScenarioTriggerType::QuestIntro,
            [
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Les routes sont devenues instables depuis que les esprits ont disparu.'],
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Choisis bien tes compagnons et reprends la ville étape par étape.'],
                ['npc_id' => $npcs['instructeur']->id, 'text' => "Commence par le centre d'entraînement, puis ouvre la route vers le carrefour.", 'action_type' => ScenarioActionType::StartTraining],
            ],
        );

        $scenarios['zone-1-intro'] = $this->seedScenario(
            $domain->id,
            'zone-1-intro',
            'Ville débutante',
            ScenarioTriggerType::ZoneIntro,
            [
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Bienvenue dans la Ville débutante. Chaque rue teste une notion du code de la route.'],
                ['npc_id' => $npcs['instructeur']->id, 'text' => "Observe, révise, puis avance jusqu'au Chauffard du Carrefour."],
            ],
        );

        $scenarios['zone-1-boss-intro'] = $this->seedScenario(
            $domain->id,
            'zone-1-boss-intro',
            'Le Chauffard du Carrefour',
            ScenarioTriggerType::BossIntro,
            [
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Le boss de la ville force tous les passages et sème la panique.'],
                ['speaker_name' => 'Chauffard du Carrefour', 'text' => 'Les priorités ? Je prends tout !'],
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Reste calme, choisis le bon esprit et reprends le contrôle.', 'action_type' => ScenarioActionType::StartBattle],
            ],
        );

        $scenarios['zone-1-boss-end'] = $this->seedScenario(
            $domain->id,
            'zone-1-boss-end',
            'Carrefour apaisé',
            ScenarioTriggerType::AfterBattle,
            [
                ['npc_id' => $npcs['instructeur']->id, 'text' => 'Le carrefour respire à nouveau. Les esprits de la ville reviennent peu à peu.'],
                ['npc_id' => $npcs['marchande']->id, 'text' => 'Passe à la boutique quand tu veux. Une victoire se célèbre toujours avec du bon matériel.'],
            ],
        );

        $zone = Zone::query()->updateOrCreate(
            ['slug' => 'ville-debutante'],
            [
                'learning_domain_id' => $domain->id,
                'name' => 'Ville débutante',
                'name_translations' => ['fr' => 'Ville débutante'],
                'description' => "Premier hub d'apprentissage avec ruelles, priorités et mini-défis urbains.",
                'description_translations' => ['fr' => "Premier hub d'apprentissage avec ruelles, priorités et mini-défis urbains."],
                'sort_order' => 1,
                'required_user_level' => 1,
                'scenario_intro_id' => $scenarios['zone-1-intro']->id,
            ],
        );

        $enemies = collect([
            ['name' => 'Cône Frondeur', 'slug' => 'cone-frondeur', 'type' => EnemyType::Monster, 'level' => 1, 'hp' => 55, 'attack' => 13, 'defense' => 8, 'speed' => 9, 'is_boss' => false, 'reward_xp' => 30, 'reward_coins' => 20, 'description' => 'Un esprit turbulent qui bloque les panneaux.', 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Brume Distrayante', 'slug' => 'brume-distrayante', 'type' => EnemyType::Monster, 'level' => 2, 'hp' => 62, 'attack' => 14, 'defense' => 9, 'speed' => 11, 'is_boss' => false, 'reward_xp' => 32, 'reward_coins' => 22, 'description' => "Elle brouille les repères et pousse à l'erreur.", 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Scooter Fantôme', 'slug' => 'scooter-fantome', 'type' => EnemyType::Monster, 'level' => 3, 'hp' => 65, 'attack' => 15, 'defense' => 10, 'speed' => 13, 'is_boss' => false, 'reward_xp' => 34, 'reward_coins' => 24, 'description' => 'Rapide et imprudent, il traverse les rues sans prévenir.', 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Klaxon Grincheux', 'slug' => 'klaxon-grincheux', 'type' => EnemyType::Monster, 'level' => 4, 'hp' => 70, 'attack' => 16, 'defense' => 11, 'speed' => 12, 'is_boss' => false, 'reward_xp' => 36, 'reward_coins' => 26, 'description' => 'Il déstabilise les esprits par son vacarme.', 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Piéton Pressé', 'slug' => 'pieton-presse', 'type' => EnemyType::Monster, 'level' => 4, 'hp' => 68, 'attack' => 16, 'defense' => 10, 'speed' => 14, 'is_boss' => false, 'reward_xp' => 36, 'reward_coins' => 26, 'description' => 'Il surgit toujours au mauvais moment.', 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Chauffard du Carrefour', 'slug' => 'chauffard-du-carrefour', 'type' => EnemyType::Boss, 'level' => 5, 'hp' => 110, 'attack' => 20, 'defense' => 13, 'speed' => 14, 'is_boss' => true, 'reward_xp' => 50, 'reward_coins' => 60, 'description' => 'Le boss urbain qui refuse toute priorité.', 'image' => 'images/monsters/imp/walk-vanilla.png'],
            ['name' => 'Seigneur Radar', 'slug' => 'seigneur-radar', 'type' => EnemyType::Boss, 'level' => 7, 'hp' => 120, 'attack' => 22, 'defense' => 15, 'speed' => 18, 'is_boss' => true, 'reward_xp' => 60, 'reward_coins' => 75, 'description' => "Une autorité corrompue qui punit l'excès sans justice.", 'image' => 'images/monsters/imp/walk-vanilla.png'],
        ])->mapWithKeys(function (array $enemyData) use ($domain, $zone) {
            $enemy = Enemy::query()->updateOrCreate(
                ['slug' => $enemyData['slug']],
                array_merge($enemyData, [
                    'name_translations' => ['fr' => $enemyData['name']],
                    'description_translations' => ['fr' => $enemyData['description']],
                    'learning_domain_id' => $domain->id,
                    'zone_id' => $enemyData['slug'] === 'seigneur-radar' ? null : $zone->id,
                    'skill_set' => [['type' => 'attack', 'power' => 4]],
                ]),
            );

            return [$enemy->slug => $enemy];
        });

        $levels = [
            ['number' => 1, 'name' => 'Ruelle des Panneaux', 'slug' => 'ruelle-des-panneaux', 'enemy' => 'cone-frondeur', 'reward_xp' => 15, 'reward_coins' => 15],
            ['number' => 2, 'name' => 'Place de la Priorité', 'slug' => 'place-de-la-priorite', 'enemy' => 'brume-distrayante', 'reward_xp' => 18, 'reward_coins' => 18],
            ['number' => 3, 'name' => 'Boulevard de la Vitesse', 'slug' => 'boulevard-de-la-vitesse', 'enemy' => 'scooter-fantome', 'reward_xp' => 20, 'reward_coins' => 20],
            ['number' => 4, 'name' => 'Passage des Dangers', 'slug' => 'passage-des-dangers', 'enemy' => 'klaxon-grincheux', 'reward_xp' => 24, 'reward_coins' => 24],
            ['number' => 5, 'name' => 'Carrefour du Boss', 'slug' => 'carrefour-du-boss', 'enemy' => 'chauffard-du-carrefour', 'reward_xp' => 40, 'reward_coins' => 45, 'boss' => true, 'scenario_id' => $scenarios['zone-1-boss-intro']->id],
        ];

        foreach ($levels as $levelData) {
            Level::query()->updateOrCreate(
                ['slug' => $levelData['slug']],
                [
                    'zone_id' => $zone->id,
                    'name' => $levelData['name'],
                    'name_translations' => ['fr' => $levelData['name']],
                    'level_number' => $levelData['number'],
                    'enemy_id' => $enemies[$levelData['enemy']]->id,
                    'required_user_level' => 1,
                    'required_creature_level' => $levelData['number'] === 5 ? 3 : null,
                    'is_boss_level' => $levelData['boss'] ?? false,
                    'scenario_id' => $levelData['scenario_id'] ?? null,
                    'reward_xp' => $levelData['reward_xp'],
                    'reward_coins' => $levelData['reward_coins'],
                ],
            );
        }
    }

    protected function seedScenario(
        int $domainId,
        string $slug,
        string $title,
        ScenarioTriggerType $triggerType,
        array $steps,
    ): Scenario {
        $scenario = Scenario::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'learning_domain_id' => $domainId,
                'title' => $title,
                'title_translations' => ['fr' => $title],
                'trigger_type' => $triggerType,
                'is_active' => true,
            ],
        );

        $scenario->steps()->delete();

        foreach ($steps as $index => $stepData) {
            ScenarioStep::query()->create([
                'scenario_id' => $scenario->id,
                'sort_order' => $index + 1,
                'npc_id' => $stepData['npc_id'] ?? null,
                'speaker_name' => $stepData['speaker_name'] ?? null,
                'speaker_name_translations' => isset($stepData['speaker_name']) ? ['fr' => $stepData['speaker_name']] : null,
                'text' => $stepData['text'],
                'text_translations' => ['fr' => $stepData['text']],
                'action_type' => $stepData['action_type'] ?? null,
                'action_payload' => $stepData['action_payload'] ?? null,
            ]);
        }

        return $scenario;
    }
}
