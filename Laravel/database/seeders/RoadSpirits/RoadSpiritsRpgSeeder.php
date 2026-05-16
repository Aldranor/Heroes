<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\CreatureAcquisitionMethod;
use App\Enums\Rarity;
use App\Enums\SkillTarget;
use App\Enums\SkillType;
use App\Models\Creatures\Creature;
use App\Models\Creatures\Skill;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use Illuminate\Database\Seeder;

class RoadSpiritsRpgSeeder extends Seeder
{
    public function run(): void
    {
        $domain = LearningDomain::query()->where('slug', 'driving_license_be')->firstOrFail();
        $categories = LearningCategory::query()
            ->where('learning_domain_id', $domain->id)
            ->get()
            ->keyBy('slug');

        $skills = collect([
            [
                'name' => 'Attaque simple',
                'slug' => 'attaque-simple',
                'description' => 'Une attaque fiable pour entamer le combat.',
                'type' => SkillType::Attack,
                'power' => 15,
                'target' => SkillTarget::Enemy,
                'unlock_level' => 1,
            ],
            [
                'name' => 'Défense',
                'slug' => 'defense-gardee',
                'description' => 'Réduit fortement les dégâts du prochain coup.',
                'type' => SkillType::Defense,
                'power' => 0,
                'target' => SkillTarget::Self,
                'unlock_level' => 3,
            ],
            [
                'name' => 'Focus',
                'slug' => 'focus',
                'description' => 'Prepare une frappe plus puissante au prochain tour.',
                'type' => SkillType::Buff,
                'power' => 8,
                'target' => SkillTarget::Self,
                'unlock_level' => 5,
            ],
            [
                'name' => 'Soin léger',
                'slug' => 'soin-leger',
                'description' => 'Récupère un peu de vitalité.',
                'type' => SkillType::Heal,
                'power' => 20,
                'target' => SkillTarget::Self,
                'unlock_level' => 5,
            ],
            [
                'name' => 'Anticipation',
                'slug' => 'anticipation',
                'description' => "Affaiblit temporairement l'attaque adverse.",
                'type' => SkillType::Debuff,
                'power' => 6,
                'target' => SkillTarget::Enemy,
                'unlock_level' => 5,
            ],
            [
                'name' => 'Attaque rapide',
                'slug' => 'attaque-rapide',
                'description' => 'Une frappe légère mais vive.',
                'type' => SkillType::Attack,
                'power' => 10,
                'target' => SkillTarget::Enemy,
                'unlock_level' => 3,
            ],
        ])->mapWithKeys(function (array $skillData) {
            $skill = Skill::query()->updateOrCreate(
                ['slug' => $skillData['slug']],
                array_merge($skillData, [
                    'name_translations' => ['fr' => $skillData['name']],
                    'description_translations' => ['fr' => $skillData['description']],
                ]),
            );

            return [$skill->slug => $skill];
        });

        $creatures = [
            ['name' => 'Gardien des Panneaux', 'slug' => 'gardien-des-panneaux', 'category' => 'signs', 'rarity' => Rarity::Common, 'hp' => 92, 'attack' => 16, 'defense' => 18, 'speed' => 12, 'starter' => true, 'unlock' => 1, 'method' => CreatureAcquisitionMethod::Starter, 'theme' => 'Armure de route et bouclier signalétique', 'personality' => 'Calme et protecteur', 'description' => 'Il encaisse les erreurs des jeunes conducteurs et apprend à observer.' ],
            ['name' => 'Hibou Signal', 'slug' => 'hibou-signal', 'category' => 'signs', 'rarity' => Rarity::Rare, 'hp' => 78, 'attack' => 15, 'defense' => 14, 'speed' => 20, 'starter' => false, 'unlock' => 2, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Plumage néon et yeux réflecteurs', 'personality' => 'Sage et nocturne', 'description' => 'Il voit les panneaux avant tout le monde, même dans la pénombre.' ],
            ['name' => 'Renard Balise', 'slug' => 'renard-balise', 'category' => 'signs', 'rarity' => Rarity::Common, 'hp' => 82, 'attack' => 18, 'defense' => 13, 'speed' => 18, 'starter' => false, 'unlock' => 3, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Queue phosphorescente et museau vif', 'personality' => 'Rusé et vif', 'description' => 'Il file de repère en repère pour guider son équipe.' ],
            ['name' => 'Stratège des Priorités', 'slug' => 'stratege-des-priorites', 'category' => 'priority', 'rarity' => Rarity::Common, 'hp' => 88, 'attack' => 15, 'defense' => 19, 'speed' => 14, 'starter' => true, 'unlock' => 1, 'method' => CreatureAcquisitionMethod::Starter, 'theme' => 'Cape tactique et compas', 'personality' => 'Méthodique et posé', 'description' => 'Il lit les carrefours comme un échiquier.' ],
            ['name' => 'Tortue Carrefour', 'slug' => 'tortue-carrefour', 'category' => 'priority', 'rarity' => Rarity::Rare, 'hp' => 98, 'attack' => 13, 'defense' => 21, 'speed' => 8, 'starter' => false, 'unlock' => 4, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Carapace peinte de lignes blanches', 'personality' => 'Patiente et solide', 'description' => 'Elle bloque les assauts et impose un rythme prudent.' ],
            ['name' => 'Lynx Cédez-le-passage', 'slug' => 'lynx-cedez-le-passage', 'category' => 'priority', 'rarity' => Rarity::Rare, 'hp' => 80, 'attack' => 19, 'defense' => 14, 'speed' => 18, 'starter' => false, 'unlock' => 5, 'method' => CreatureAcquisitionMethod::Arena, 'theme' => 'Pelage marqué de triangles blancs', 'personality' => 'Lucide et tranchant', 'description' => "Il détecte l'ouverture parfaite avant d'agir." ],
            ['name' => 'Éclaireur de Vitesse', 'slug' => 'eclaireur-de-vitesse', 'category' => 'speed', 'rarity' => Rarity::Common, 'hp' => 76, 'attack' => 18, 'defense' => 13, 'speed' => 22, 'starter' => true, 'unlock' => 1, 'method' => CreatureAcquisitionMethod::Starter, 'theme' => 'Veste de vent et bottes légères', 'personality' => 'Enthousiaste et rapide', 'description' => 'Il apprend à aller vite, mais jamais sans contrôle.' ],
            ['name' => 'Guépard Radar', 'slug' => 'guepard-radar', 'category' => 'speed', 'rarity' => Rarity::Rare, 'hp' => 72, 'attack' => 21, 'defense' => 12, 'speed' => 24, 'starter' => false, 'unlock' => 4, 'method' => CreatureAcquisitionMethod::Arena, 'theme' => 'Traits lumineux et masque de visée', 'personality' => 'Percutant et précis', 'description' => "Il transforme la vitesse en art de l'ajustement." ],
            ['name' => 'Colibri Chrono', 'slug' => 'colibri-chrono', 'category' => 'speed', 'rarity' => Rarity::Epic, 'hp' => 68, 'attack' => 17, 'defense' => 11, 'speed' => 26, 'starter' => false, 'unlock' => 7, 'method' => CreatureAcquisitionMethod::Achievement, 'theme' => 'Ailes irisées et traînées de temps', 'personality' => 'Nerveux et brillant', 'description' => 'Ses mouvements semblent toujours une seconde en avance.' ],
            ['name' => 'Bouclier Ceinture', 'slug' => 'bouclier-ceinture', 'category' => 'safety', 'rarity' => Rarity::Common, 'hp' => 94, 'attack' => 14, 'defense' => 20, 'speed' => 10, 'starter' => false, 'unlock' => 2, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Plaques renforcées et sangles lumineuses', 'personality' => 'Loyal et stable', 'description' => 'Il protège le groupe contre les erreurs de concentration.' ],
            ['name' => 'Panda Prudence', 'slug' => 'panda-prudence', 'category' => 'safety', 'rarity' => Rarity::Rare, 'hp' => 90, 'attack' => 15, 'defense' => 17, 'speed' => 13, 'starter' => false, 'unlock' => 4, 'method' => CreatureAcquisitionMethod::Shop, 'theme' => "Manteau souple et coussins d'impact", 'personality' => 'Doux et attentif', 'description' => "Il calme le rythme et remet l'équipe en sécurité." ],
            ['name' => 'Hérisson Distance', 'slug' => 'herisson-distance', 'category' => 'safety', 'rarity' => Rarity::Common, 'hp' => 86, 'attack' => 16, 'defense' => 18, 'speed' => 12, 'starter' => false, 'unlock' => 3, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Pointes souples en arcs de distance', 'personality' => 'Prudent et tenace', 'description' => "Il rappelle sans cesse l'importance de garder de l'espace." ],
            ['name' => 'Corbeau Anticipation', 'slug' => 'corbeau-anticipation', 'category' => 'dangers', 'rarity' => Rarity::Common, 'hp' => 80, 'attack' => 17, 'defense' => 14, 'speed' => 20, 'starter' => false, 'unlock' => 2, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => "Ailes d'orage et regard vif", 'personality' => 'Alerte et critique', 'description' => "Il détecte les dangers avant qu'ils prennent forme." ],
            ['name' => 'Loup Brouillard', 'slug' => 'loup-brouillard', 'category' => 'dangers', 'rarity' => Rarity::Rare, 'hp' => 88, 'attack' => 18, 'defense' => 15, 'speed' => 16, 'starter' => false, 'unlock' => 5, 'method' => CreatureAcquisitionMethod::Adventure, 'theme' => 'Pelage gris perle et souffle brumeux', 'personality' => 'Instinctif et silencieux', 'description' => "Il navigue dans l'incertitude avec sang-froid." ],
            ['name' => 'Salamandre Pluie', 'slug' => 'salamandre-pluie', 'category' => 'dangers', 'rarity' => Rarity::Rare, 'hp' => 84, 'attack' => 17, 'defense' => 16, 'speed' => 17, 'starter' => false, 'unlock' => 6, 'method' => CreatureAcquisitionMethod::Arena, 'theme' => "Peau vernie et lueurs d'averse", 'personality' => 'Souple et réactive', 'description' => 'Elle reste stable là où la route devient glissante.' ],
            ['name' => 'Dragon Permis', 'slug' => 'dragon-permis', 'category' => 'mixed', 'rarity' => Rarity::Epic, 'hp' => 92, 'attack' => 19, 'defense' => 18, 'speed' => 15, 'starter' => false, 'unlock' => 8, 'method' => CreatureAcquisitionMethod::Achievement, 'theme' => "Écailles de parchemin et souffle d'encre", 'personality' => 'Noble et exigeant', 'description' => 'Il incarne la maîtrise globale du code et de la route.' ],
            ['name' => 'Chat Réflexe', 'slug' => 'chat-reflexe', 'category' => 'mixed', 'rarity' => Rarity::Rare, 'hp' => 82, 'attack' => 18, 'defense' => 15, 'speed' => 21, 'starter' => false, 'unlock' => 5, 'method' => CreatureAcquisitionMethod::Shop, 'theme' => 'Silhouette souple et yeux brillants', 'personality' => 'Malin et agile', 'description' => "Il réagit vite et s'adapte à presque tout." ],
            ['name' => 'Phénix Révision', 'slug' => 'phenix-revision', 'category' => 'mixed', 'rarity' => Rarity::Epic, 'hp' => 90, 'attack' => 17, 'defense' => 17, 'speed' => 19, 'starter' => false, 'unlock' => 9, 'method' => CreatureAcquisitionMethod::Achievement, 'theme' => 'Plumes flamboyantes et notes de révision', 'personality' => 'Inspiré et résilient', 'description' => 'Chaque erreur devient pour lui une nouvelle montée en puissance.' ],
        ];

        foreach ($creatures as $creatureData) {
            $category = $categories[$creatureData['category']];
            $package = $this->skillPackageForCategory($creatureData['category']);
            $defaultSkillSlug = collect($package)->firstWhere('is_default', true)['skill'];

            $creature = Creature::query()->updateOrCreate(
                ['slug' => $creatureData['slug']],
                [
                    'name' => $creatureData['name'],
                    'name_translations' => ['fr' => $creatureData['name']],
                    'learning_domain_id' => $domain->id,
                    'learning_category_id' => $category->id,
                    'rarity' => $creatureData['rarity'],
                    'base_hp' => $creatureData['hp'],
                    'base_attack' => $creatureData['attack'],
                    'base_defense' => $creatureData['defense'],
                    'base_speed' => $creatureData['speed'],
                    'description' => $creatureData['description'],
                    'description_translations' => ['fr' => $creatureData['description']],
                    'visual_theme' => $creatureData['theme'],
                    'visual_theme_translations' => ['fr' => $creatureData['theme']],
                    'personality' => $creatureData['personality'],
                    'personality_translations' => ['fr' => $creatureData['personality']],
                    'starter_allowed' => $creatureData['starter'],
                    'unlock_level' => $creatureData['unlock'],
                    'acquisition_method' => $creatureData['method'],
                    'default_skill_id' => $skills[$defaultSkillSlug]->id,
                ],
            );

            $creature->skills()->sync(
                collect($package)->mapWithKeys(fn (array $skillConfig) => [
                    $skills[$skillConfig['skill']]->id => [
                        'unlock_level' => $skillConfig['unlock_level'],
                        'sort_order' => $skillConfig['sort_order'],
                        'is_default' => $skillConfig['is_default'],
                    ],
                ])->all(),
            );
        }
    }

    protected function skillPackageForCategory(string $categorySlug): array
    {
        return match ($categorySlug) {
            'signs' => [
                ['skill' => 'attaque-simple', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'defense-gardee', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'focus', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'soin-leger', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
            'priority' => [
                ['skill' => 'attaque-simple', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'anticipation', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'defense-gardee', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'focus', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
            'speed' => [
                ['skill' => 'attaque-rapide', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'focus', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'attaque-simple', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'defense-gardee', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
            'safety' => [
                ['skill' => 'attaque-simple', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'soin-leger', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'defense-gardee', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'focus', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
            'dangers' => [
                ['skill' => 'attaque-simple', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'anticipation', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'attaque-rapide', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'focus', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
            default => [
                ['skill' => 'attaque-simple', 'unlock_level' => 1, 'sort_order' => 1, 'is_default' => true],
                ['skill' => 'focus', 'unlock_level' => 3, 'sort_order' => 2, 'is_default' => false],
                ['skill' => 'anticipation', 'unlock_level' => 5, 'sort_order' => 3, 'is_default' => false],
                ['skill' => 'soin-leger', 'unlock_level' => 8, 'sort_order' => 4, 'is_default' => false],
            ],
        };
    }
}
