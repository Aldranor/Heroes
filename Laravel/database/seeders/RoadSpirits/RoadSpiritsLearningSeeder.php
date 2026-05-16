<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoadSpiritsLearningSeeder extends Seeder
{
    public function run(): void
    {
        $domain = LearningDomain::query()->updateOrCreate(
            ['slug' => 'driving_license_be'],
            [
                'name' => 'Code de la route belge',
                'name_translations' => ['fr' => 'Code de la route belge'],
                'description' => 'Plateforme de revision pensee pour mobile afin d\'apprendre les regles de circulation en Belgique.',
                'description_translations' => ['fr' => 'Plateforme de revision pensee pour mobile afin d\'apprendre les regles de circulation en Belgique.'],
                'locale' => 'fr_BE',
                'country' => 'BE',
                'is_active' => true,
                'icon' => 'road',
            ],
        );

        $categories = collect([
            [
                'name' => 'Signalisation',
                'slug' => 'signs',
                'name_translations' => ['fr' => 'Signalisation'],
                'description' => 'Panneaux, obligations et indications visuelles.',
                'description_translations' => ['fr' => 'Panneaux, obligations et indications visuelles.'],
                'color' => '#C96D42',
                'icon' => 'signpost',
                'sort_order' => 1,
            ],
            [
                'name' => 'Priorités',
                'slug' => 'priority',
                'name_translations' => ['fr' => 'Priorités'],
                'description' => 'Règles de priorité et gestion des carrefours.',
                'description_translations' => ['fr' => 'Règles de priorité et gestion des carrefours.'],
                'color' => '#3F7C85',
                'icon' => 'intersection',
                'sort_order' => 2,
            ],
            [
                'name' => 'Vitesse',
                'slug' => 'speed',
                'name_translations' => ['fr' => 'Vitesse'],
                'description' => "Vitesses maximales et adaptation de l'allure.",
                'description_translations' => ['fr' => "Vitesses maximales et adaptation de l'allure."],
                'color' => '#E0A458',
                'icon' => 'gauge',
                'sort_order' => 3,
            ],
            [
                'name' => 'Sécurité',
                'slug' => 'safety',
                'name_translations' => ['fr' => 'Sécurité'],
                'description' => 'Ceinture, équipements et vigilance au volant.',
                'description_translations' => ['fr' => 'Ceinture, équipements et vigilance au volant.'],
                'color' => '#5B8C5A',
                'icon' => 'shield',
                'sort_order' => 4,
            ],
            [
                'name' => 'Dangers',
                'slug' => 'dangers',
                'name_translations' => ['fr' => 'Dangers'],
                'description' => 'Conditions difficiles et anticipation des risques.',
                'description_translations' => ['fr' => 'Conditions difficiles et anticipation des risques.'],
                'color' => '#8C4A3F',
                'icon' => 'warning',
                'sort_order' => 5,
            ],
            [
                'name' => 'Mixte',
                'slug' => 'mixed',
                'name_translations' => ['fr' => 'Mixte'],
                'description' => 'Révision globale multi-catégories.',
                'description_translations' => ['fr' => 'Révision globale multi-catégories.'],
                'color' => '#6A5ACD',
                'icon' => 'sparkles',
                'sort_order' => 6,
                'is_mixed' => true,
            ],
        ])->mapWithKeys(function (array $categoryData) use ($domain) {
            $category = LearningCategory::query()->updateOrCreate(
                [
                    'learning_domain_id' => $domain->id,
                    'slug' => $categoryData['slug'],
                ],
                array_merge($categoryData, [
                    'learning_domain_id' => $domain->id,
                ]),
            );

            return [$category->slug => $category];
        });

        $questions = [
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Que signifie un panneau STOP octogonal rouge ?',
                'explanation' => "Le STOP impose un arrêt complet à la ligne ou avant l'intersection, puis le passage se fait seulement si la voie est libre.",
                'answers' => [
                    ['text' => "Je ralentis puis je passe si personne n'arrive", 'correct' => false],
                    ['text' => "Je m'arrête complètement avant de repartir si la voie est libre", 'correct' => true],
                    ['text' => "J'ai la priorité si je suis déjà engagé", 'correct' => false],
                    ['text' => "Je peux passer sans m'arrêter la nuit", 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Un panneau rond bleu avec une flèche blanche vers la droite indique :',
                'explanation' => 'Un panneau rond bleu indique souvent une obligation. Ici, il faut suivre la direction imposée vers la droite.',
                'answers' => [
                    ['text' => 'Une suggestion de tourner à droite', 'correct' => false],
                    ['text' => 'Un sens interdit', 'correct' => false],
                    ['text' => 'Une obligation de tourner ou de suivre vers la droite', 'correct' => true],
                    ['text' => 'Une priorité à droite', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Que signifie un panneau rond bord rouge avec le nombre 50 ?',
                'explanation' => "Ce panneau fixe une vitesse maximale autorisée à 50 km/h jusqu'à nouvel ordre.",
                'answers' => [
                    ['text' => 'Une vitesse conseillée de 50 km/h', 'correct' => false],
                    ['text' => 'Une vitesse minimale de 50 km/h', 'correct' => false],
                    ['text' => 'Une vitesse maximale de 50 km/h', 'correct' => true],
                    ['text' => 'La fin de la limitation à 50 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un triangle rouge avec des enfants signale généralement :',
                'explanation' => "Le panneau de danger avertit d'un lieu fréquenté par des enfants, comme une école ou une zone proche.",
                'answers' => [
                    ['text' => 'Une aire de jeux réservée aux piétons', 'correct' => false],
                    ['text' => "Un passage probable ou fréquent d'enfants", 'correct' => true],
                    ['text' => 'Une interdiction pour les enfants', 'correct' => false],
                    ['text' => 'Une zone de vitesse libre', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => "En l'absence de signalisation particulière à un carrefour, quelle règle s'applique en général ?",
                'explanation' => "En Belgique, à défaut d'indication contraire, la priorité de droite s'applique dans de nombreux carrefours.",
                'answers' => [
                    ['text' => 'La priorité au véhicule le plus rapide', 'correct' => false],
                    ['text' => 'La priorité au véhicule venant de droite', 'correct' => true],
                    ['text' => 'La priorité au plus gros véhicule', 'correct' => false],
                    ['text' => 'La priorité à celui qui klaxonne', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Que devez-vous faire face à un panneau cédez-le-passage ?',
                'explanation' => "Il faut ralentir et, si nécessaire, s'arrêter pour laisser passer les usagers prioritaires.",
                'answers' => [
                    ['text' => 'Continuer sans ralentir si la route semble libre', 'correct' => false],
                    ['text' => 'Ralentir et laisser passer les usagers prioritaires', 'correct' => true],
                    ['text' => "Toujours s'arrêter complètement", 'correct' => false],
                    ['text' => 'Forcer le passage avant les piétons', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => "Au panneau STOP, quelle est l'obligation minimale ?",
                'explanation' => "Le STOP impose toujours l'arrêt complet, même si la route paraît libre.",
                'answers' => [
                    ['text' => 'Simplement regarder à gauche', 'correct' => false],
                    ['text' => "Ralentir fortement sans s'arrêter", 'correct' => false],
                    ['text' => "S'arrêter complètement puis repartir si la voie est libre", 'correct' => true],
                    ['text' => "Passer en premier si l'on est déjà au milieu du carrefour", 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Si des feux de circulation fonctionnent normalement, ils priment en général sur :',
                'explanation' => "Les feux réglementent la circulation et s'appliquent tant qu'un agent qualifié ne donne pas d'ordre contraire.",
                'answers' => [
                    ['text' => 'Les panneaux fixes de priorité du carrefour', 'correct' => true],
                    ['text' => 'La largeur de la route', 'correct' => false],
                    ['text' => 'Les conditions météo', 'correct' => false],
                    ['text' => 'La vitesse du véhicule derrière vous', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => "Sans autre indication, quelle vitesse maximale s'applique en général en agglomération ?",
                'explanation' => 'La limite générale en agglomération est souvent de 50 km/h, sauf signalisation spécifique ou règlement local plus strict.',
                'answers' => [
                    ['text' => '30 km/h', 'correct' => false],
                    ['text' => '50 km/h', 'correct' => true],
                    ['text' => '70 km/h', 'correct' => false],
                    ['text' => '90 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Dans une zone résidentielle clairement signalée, quelle allure doit-on en principe respecter ?',
                'explanation' => 'La zone résidentielle implique une circulation très apaisée, en principe limitée à 20 km/h.',
                'answers' => [
                    ['text' => '20 km/h', 'correct' => true],
                    ['text' => '30 km/h', 'correct' => false],
                    ['text' => '50 km/h', 'correct' => false],
                    ['text' => '70 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => "Si un panneau de limitation à 30 km/h est placé à l'entrée d'une zone, vous devez :",
                'explanation' => "La limitation indiquée s'applique tant qu'elle n'est pas levée ou remplacée par une autre règle.",
                'answers' => [
                    ['text' => 'Rouler à 30 km/h maximum', 'correct' => true],
                    ['text' => 'Rouler à minimum 30 km/h', 'correct' => false],
                    ['text' => 'Rouler à 50 km/h si la rue est vide', 'correct' => false],
                    ['text' => 'Suivre la vitesse du véhicule devant', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'La ceinture de sécurité est-elle réservée au conducteur ?',
                'explanation' => 'La ceinture concerne tous les occupants lorsque le véhicule en est équipé.',
                'answers' => [
                    ['text' => 'Oui, seulement le conducteur', 'correct' => false],
                    ['text' => 'Non, elle concerne aussi les passagers', 'correct' => true],
                    ['text' => 'Seulement sur autoroute', 'correct' => false],
                    ['text' => "Seulement à l'avant du véhicule", 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un enfant de petite taille doit en général voyager :',
                'explanation' => 'Les enfants doivent utiliser un dispositif de retenue adapté à leur taille, notamment sous 135 cm.',
                'answers' => [
                    ['text' => "Sans dispositif, du moment qu'il est assis derrière", 'correct' => false],
                    ['text' => 'Avec un dispositif de retenue adapté', 'correct' => true],
                    ['text' => "Seulement sur les genoux d'un adulte", 'correct' => false],
                    ['text' => "À l'avant sans ceinture", 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Tenir son téléphone en main en conduisant est :',
                'explanation' => "L'usage d'un téléphone tenu en main distrait et est interdit. Il faut éviter toute manipulation dangereuse.",
                'answers' => [
                    ['text' => 'Autorisé à basse vitesse', 'correct' => false],
                    ['text' => 'Autorisé dans les embouteillages', 'correct' => false],
                    ['text' => 'Interdit', 'correct' => true],
                    ['text' => 'Obligatoire pour la navigation', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Par fort brouillard, quelle réaction est la plus prudente ?',
                'explanation' => 'Le brouillard réduit fortement la visibilité : il faut ralentir, rester visible et utiliser les feux appropriés selon les conditions.',
                'answers' => [
                    ['text' => 'Accélérer pour sortir vite de la zone', 'correct' => false],
                    ['text' => 'Ralentir et utiliser les feux appropriés', 'correct' => true],
                    ['text' => 'Rouler au milieu de la route pour mieux voir', 'correct' => false],
                    ['text' => 'Éteindre les feux pour éviter les reflets', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Sous la pluie, la distance de sécurité doit plutôt :',
                'explanation' => "L'adhérence baisse sur route mouillée. Il faut donc augmenter la distance avec le véhicule devant.",
                'answers' => [
                    ['text' => 'Diminuer pour ne pas se faire dépasser', 'correct' => false],
                    ['text' => 'Rester identique en toute situation', 'correct' => false],
                    ['text' => 'Augmenter', 'correct' => true],
                    ['text' => 'Dépendre uniquement de la taille du véhicule', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Si vous sentez la fatigue monter au volant, la meilleure attitude est :',
                'explanation' => "La fatigue nuit fortement à l'attention et au temps de réaction. Il faut s'arrêter et se reposer.",
                'answers' => [
                    ['text' => 'Ouvrir la fenêtre et continuer', 'correct' => false],
                    ['text' => 'Boire uniquement un café et rouler plus vite', 'correct' => false],
                    ['text' => 'Faire une pause en lieu sûr et se reposer', 'correct' => true],
                    ['text' => 'Suivre de plus près le véhicule devant', 'correct' => false],
                ],
            ],
            [
                'category' => 'mixed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Devant un passage piéton ou une personne commence à traverser, vous devez :',
                'explanation' => "Le conducteur doit rester attentif à la traversée des piétons et leur laisser le passage lorsque la situation l'exige.",
                'answers' => [
                    ['text' => "Klaxonner pour qu'elle accélère", 'correct' => false],
                    ['text' => 'Continuer si vous êtes déjà proche', 'correct' => false],
                    ['text' => 'Ralentir et vous arrêter si nécessaire pour la laisser passer', 'correct' => true],
                    ['text' => 'La contourner par la gauche', 'correct' => false],
                ],
            ],
            [
                'category' => 'mixed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Vous êtes à un STOP et une voiture arrive sur la route prioritaire. Quelle action est correcte ?',
                'explanation' => "Le STOP impose l'arrêt complet et le respect des usagers prioritaires avant de repartir.",
                'answers' => [
                    ['text' => "Vous passez d'abord si vous avez freiné fort", 'correct' => false],
                    ['text' => 'Vous vous arrêtez et vous attendez que la voie soit libre', 'correct' => true],
                    ['text' => "Vous avancez doucement jusqu'au milieu du carrefour", 'correct' => false],
                    ['text' => 'Vous passez si le véhicule est encore loin', 'correct' => false],
                ],
            ],
            [
                'category' => 'mixed',
                'difficulty' => QuestionDifficulty::Hard,
                'question_text' => 'En ville, sous une forte pluie et avec une visibilité réduite, la conduite la plus sûre consiste à :',
                'explanation' => 'Quand les conditions sont mauvaises, il faut adapter sa vitesse, augmenter les distances et surveiller les usagers vulnérables.',
                'answers' => [
                    ['text' => "Maintenir la vitesse maximale autorisée quoi qu'il arrive", 'correct' => false],
                    ['text' => 'Ralentir, augmenter la distance et rester très attentif', 'correct' => true],
                    ['text' => 'Rouler très près du véhicule devant pour mieux voir', 'correct' => false],
                    ['text' => 'Freiner le moins possible pour éviter les glissades', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Que signifie un signal triangulaire à bord rouge ?',
                'explanation' => "Un signal triangulaire à bord rouge avertit d'un danger. Le conducteur doit adapter son allure et redoubler d'attention.",
                'answers' => [
                    ['text' => 'Une obligation immédiate', 'correct' => false],
                    ['text' => 'Un danger annoncé', 'correct' => true],
                    ['text' => 'Une zone de stationnement', 'correct' => false],
                    ['text' => 'Une fin de limitation', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Un panneau circulaire rouge avec une barre blanche horizontale indique :',
                'explanation' => "Ce signal interdit l'accès dans ce sens. Il ne faut pas s'engager dans la rue ou la voie concernée.",
                'answers' => [
                    ['text' => 'Une route réservée aux riverains', 'correct' => false],
                    ['text' => 'Un sens interdit', 'correct' => true],
                    ['text' => 'Une priorité de passage', 'correct' => false],
                    ['text' => 'Une obligation de s’arrêter', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un panneau triangulaire avec un point d’exclamation signale :',
                'explanation' => "Le point d’exclamation annonce un danger qui n'est pas précisé par un autre symbole. Il faut ralentir et observer la situation.",
                'answers' => [
                    ['text' => 'Un danger non précisé', 'correct' => true],
                    ['text' => 'Une obligation de demi-tour', 'correct' => false],
                    ['text' => 'Une zone sans priorité', 'correct' => false],
                    ['text' => 'Une route réservée aux voitures', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Des panneaux temporaires placés pour des travaux indiquent une limitation plus basse que la règle générale. Que devez-vous faire ?',
                'explanation' => 'La signalisation temporaire de chantier doit être respectée. Elle adapte la règle générale à une situation locale dangereuse.',
                'answers' => [
                    ['text' => 'Respecter la limitation temporaire', 'correct' => true],
                    ['text' => 'Garder la limite générale si la route semble libre', 'correct' => false],
                    ['text' => 'Ignorer les panneaux temporaires la nuit', 'correct' => false],
                    ['text' => 'Suivre uniquement le véhicule devant', 'correct' => false],
                ],
            ],
            [
                'category' => 'signs',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Un panneau bleu avec un P blanc indique généralement :',
                'explanation' => 'Le P blanc sur fond bleu indique un emplacement ou une zone de stationnement, sous réserve des conditions précisées sur place.',
                'answers' => [
                    ['text' => 'Un parking ou stationnement autorisé', 'correct' => true],
                    ['text' => 'Un passage obligatoire pour piétons', 'correct' => false],
                    ['text' => 'Une route prioritaire', 'correct' => false],
                    ['text' => 'Une interdiction de s’arrêter', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Vous quittez un parking privé pour rejoindre la voie publique. Quelle règle s’applique ?',
                'explanation' => 'S’engager depuis un accès privé est une manœuvre. Il faut céder le passage aux usagers qui circulent sur la voie publique.',
                'answers' => [
                    ['text' => 'Vous êtes prioritaire si vous sortez lentement', 'correct' => false],
                    ['text' => 'Vous devez céder le passage aux usagers de la voie publique', 'correct' => true],
                    ['text' => 'Vous devez seulement céder aux véhicules motorisés', 'correct' => false],
                    ['text' => 'Vous pouvez forcer le passage si le parking est plein', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un véhicule prioritaire arrive avec feu bleu clignotant et avertisseur sonore spécial. Que devez-vous faire ?',
                'explanation' => 'Il faut faciliter immédiatement son passage, sans créer de danger pour les autres usagers.',
                'answers' => [
                    ['text' => 'Accélérer pour rester devant lui', 'correct' => false],
                    ['text' => 'Faciliter son passage dès que possible', 'correct' => true],
                    ['text' => 'S’arrêter au milieu du carrefour dans tous les cas', 'correct' => false],
                    ['text' => 'Continuer normalement si vous respectez la limite', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'À un passage pour piétons non réglé, une personne est engagée. Quelle conduite est correcte ?',
                'explanation' => 'Le piéton a priorité sur le passage. Le conducteur doit ralentir et s’arrêter si nécessaire.',
                'answers' => [
                    ['text' => 'Passer si vous pensez avoir le temps', 'correct' => false],
                    ['text' => 'Ralentir et laisser passer le piéton', 'correct' => true],
                    ['text' => 'Klaxonner pour prévenir puis passer', 'correct' => false],
                    ['text' => 'Contourner le piéton par la gauche', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Hard,
                'question_text' => 'Un tram approche alors que vous voulez traverser sa trajectoire. Que faut-il retenir ?',
                'explanation' => 'Les trams bénéficient d’une priorité particulière, sauf ordre contraire d’un agent ou signalisation lumineuse applicable.',
                'answers' => [
                    ['text' => 'Le tram doit toujours céder aux voitures', 'correct' => false],
                    ['text' => 'Vous devez tenir compte de sa priorité particulière', 'correct' => true],
                    ['text' => 'La priorité de droite suffit toujours contre un tram', 'correct' => false],
                    ['text' => 'Vous pouvez passer si le tram roule lentement', 'correct' => false],
                ],
            ],
            [
                'category' => 'priority',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Dans un rond-point, vous changez de bande. Cette action est :',
                'explanation' => 'Changer de bande est une manœuvre. Il faut céder le passage aux usagers qui circulent déjà sur la bande visée.',
                'answers' => [
                    ['text' => 'Une manœuvre qui impose de céder le passage', 'correct' => true],
                    ['text' => 'Toujours prioritaire dans un rond-point', 'correct' => false],
                    ['text' => 'Interdite dans tous les cas', 'correct' => false],
                    ['text' => 'Autorisée sans clignotant', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Sur autoroute, quelle est la limitation générale pour une voiture ou un véhicule léger lorsque rien d’autre n’est indiqué ?',
                'explanation' => 'La limitation générale sur autoroute est de 120 km/h pour les véhicules légers, sauf signalisation plus restrictive.',
                'answers' => [
                    ['text' => '90 km/h', 'correct' => false],
                    ['text' => '100 km/h', 'correct' => false],
                    ['text' => '120 km/h', 'correct' => true],
                    ['text' => '130 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Dans la Région de Bruxelles-Capitale, quelle limite générale s’applique en agglomération ?',
                'explanation' => 'À Bruxelles, la règle générale en agglomération est de 30 km/h, sauf signalisation différente.',
                'answers' => [
                    ['text' => '30 km/h', 'correct' => true],
                    ['text' => '50 km/h', 'correct' => false],
                    ['text' => '70 km/h', 'correct' => false],
                    ['text' => '90 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'En Région flamande, hors agglomération, sur une route ordinaire sans signalisation différente, la limite générale pour une voiture est :',
                'explanation' => 'En Flandre, sur les autres routes hors agglomération, la limite générale est de 70 km/h pour les véhicules légers.',
                'answers' => [
                    ['text' => '50 km/h', 'correct' => false],
                    ['text' => '70 km/h', 'correct' => true],
                    ['text' => '90 km/h', 'correct' => false],
                    ['text' => '120 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'En Région wallonne, hors agglomération, sur une route ordinaire sans signalisation différente, la limite générale pour une voiture est :',
                'explanation' => 'En Wallonie, sur les autres routes hors agglomération, la limite générale est de 90 km/h pour les véhicules légers.',
                'answers' => [
                    ['text' => '50 km/h', 'correct' => false],
                    ['text' => '70 km/h', 'correct' => false],
                    ['text' => '90 km/h', 'correct' => true],
                    ['text' => '120 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Même si la limitation autorise 50 km/h, que devez-vous faire si la visibilité est mauvaise ?',
                'explanation' => 'La vitesse doit toujours être adaptée aux circonstances : visibilité, état de la route, trafic et usagers vulnérables.',
                'answers' => [
                    ['text' => 'Rouler obligatoirement à 50 km/h', 'correct' => false],
                    ['text' => 'Adapter votre vitesse aux conditions', 'correct' => true],
                    ['text' => 'Accélérer pour réduire le temps de trajet', 'correct' => false],
                    ['text' => 'Suivre la vitesse moyenne du trafic', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un signal C43 affiche 70. Que signifie-t-il ?',
                'explanation' => 'Le signal C43 fixe une vitesse maximale. Le nombre indiqué est la vitesse maximale autorisée en km/h.',
                'answers' => [
                    ['text' => 'Vitesse maximale de 70 km/h', 'correct' => true],
                    ['text' => 'Vitesse minimale de 70 km/h', 'correct' => false],
                    ['text' => 'Distance minimale de 70 mètres', 'correct' => false],
                    ['text' => 'Fin de toutes les limitations', 'correct' => false],
                ],
            ],
            [
                'category' => 'speed',
                'difficulty' => QuestionDifficulty::Hard,
                'question_text' => 'En Région wallonne, les parties de voie réservées aux piétons et cyclistes signalées D9 ou D10 sont limitées à :',
                'explanation' => 'En Wallonie, ces parties de voie publique signalées D9 ou D10 sont limitées à 30 km/h.',
                'answers' => [
                    ['text' => '20 km/h', 'correct' => false],
                    ['text' => '30 km/h', 'correct' => true],
                    ['text' => '50 km/h', 'correct' => false],
                    ['text' => '70 km/h', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'En agglomération, quelle distance latérale minimale doit-on laisser entre un véhicule motorisé et un piéton ?',
                'explanation' => 'En agglomération, les conducteurs doivent laisser au moins un mètre de distance latérale avec un piéton.',
                'answers' => [
                    ['text' => 'Au moins 50 cm', 'correct' => false],
                    ['text' => 'Au moins 1 mètre', 'correct' => true],
                    ['text' => 'Au moins 3 mètres', 'correct' => false],
                    ['text' => 'Aucune distance si la route est étroite', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Dans un embouteillage, comment faut-il gérer un passage pour piétons ?',
                'explanation' => 'Un passage pour piétons doit rester libre afin que les piétons puissent traverser en sécurité.',
                'answers' => [
                    ['text' => 'S’arrêter dessus si la file avance lentement', 'correct' => false],
                    ['text' => 'Le garder libre', 'correct' => true],
                    ['text' => 'Le bloquer seulement quelques secondes', 'correct' => false],
                    ['text' => 'L’utiliser comme zone d’attente', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Un enfant de moins de 135 cm en voiture doit généralement être installé :',
                'explanation' => 'Les enfants de moins de 135 cm doivent généralement utiliser un dispositif de retenue pour enfants adapté.',
                'answers' => [
                    ['text' => 'Dans un dispositif de retenue adapté', 'correct' => true],
                    ['text' => 'Sans ceinture si le trajet est court', 'correct' => false],
                    ['text' => 'Sur les genoux d’un adulte', 'correct' => false],
                    ['text' => 'Uniquement à l’avant', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Hard,
                'question_text' => 'Un siège enfant dos à la route peut être placé sur un siège avec airbag frontal actif ?',
                'explanation' => 'Un dispositif dos à la route ne peut être monté que si le siège est sans airbag frontal ou si cet airbag est désactivé.',
                'answers' => [
                    ['text' => 'Oui, toujours', 'correct' => false],
                    ['text' => 'Oui, si le trajet est court', 'correct' => false],
                    ['text' => 'Non, sauf si l’airbag frontal est absent ou désactivé', 'correct' => true],
                    ['text' => 'Oui, uniquement en ville', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Pourquoi faut-il régler correctement les rétroviseurs avant de démarrer ?',
                'explanation' => 'Des rétroviseurs bien réglés permettent de surveiller l’environnement et de limiter les angles morts.',
                'answers' => [
                    ['text' => 'Pour mieux entendre le trafic', 'correct' => false],
                    ['text' => 'Pour surveiller l’environnement et réduire les angles morts', 'correct' => true],
                    ['text' => 'Pour augmenter la vitesse maximale', 'correct' => false],
                    ['text' => 'Pour remplacer l’usage des clignotants', 'correct' => false],
                ],
            ],
            [
                'category' => 'safety',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'Avant d’ouvrir une portière côté circulation, il faut surtout :',
                'explanation' => 'Il faut vérifier qu’aucun cycliste, cyclomoteur ou autre usager n’arrive afin d’éviter une collision.',
                'answers' => [
                    ['text' => 'Ouvrir rapidement pour prévenir les autres', 'correct' => false],
                    ['text' => 'Vérifier qu’aucun usager n’arrive', 'correct' => true],
                    ['text' => 'Klaxonner puis ouvrir', 'correct' => false],
                    ['text' => 'Ouvrir seulement avec la portière arrière', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'En cas d’aquaplanage, quelle réaction est la plus sûre ?',
                'explanation' => 'Il faut éviter les gestes brusques, relâcher doucement l’accélérateur et garder la trajectoire aussi stable que possible.',
                'answers' => [
                    ['text' => 'Freiner brutalement', 'correct' => false],
                    ['text' => 'Relâcher doucement l’accélérateur et garder le volant stable', 'correct' => true],
                    ['text' => 'Accélérer pour reprendre l’adhérence', 'correct' => false],
                    ['text' => 'Donner un grand coup de volant', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Sur une route verglacée, quelle conduite est la plus adaptée ?',
                'explanation' => 'Le verglas réduit fortement l’adhérence. Il faut ralentir, éviter les manœuvres brusques et augmenter les distances.',
                'answers' => [
                    ['text' => 'Freiner tard et fort', 'correct' => false],
                    ['text' => 'Ralentir et conduire très progressivement', 'correct' => true],
                    ['text' => 'Suivre de près le véhicule devant', 'correct' => false],
                    ['text' => 'Rouler au point mort', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'À l’approche d’un chantier avec rétrécissement de voie, il faut :',
                'explanation' => 'Un chantier modifie la chaussée et augmente les risques. Il faut ralentir, respecter la signalisation et anticiper le rétrécissement.',
                'answers' => [
                    ['text' => 'Accélérer avant le rétrécissement', 'correct' => false],
                    ['text' => 'Ralentir et suivre la signalisation', 'correct' => true],
                    ['text' => 'Se placer au milieu des deux bandes', 'correct' => false],
                    ['text' => 'Ignorer les cônes s’il n’y a personne', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Medium,
                'question_text' => 'Dans un tunnel, pourquoi est-il important de rester attentif aux distances ?',
                'explanation' => 'Un tunnel laisse moins d’échappatoires. Garder ses distances réduit les risques de collision en chaîne.',
                'answers' => [
                    ['text' => 'Parce que les dépassements y sont toujours obligatoires', 'correct' => false],
                    ['text' => 'Pour réduire le risque de collision en chaîne', 'correct' => true],
                    ['text' => 'Pour permettre aux autres de rouler plus vite', 'correct' => false],
                    ['text' => 'Parce que les feux remplacent les freins', 'correct' => false],
                ],
            ],
            [
                'category' => 'dangers',
                'difficulty' => QuestionDifficulty::Easy,
                'question_text' => 'La consommation d’alcool avant de conduire augmente surtout :',
                'explanation' => 'L’alcool diminue l’attention, allonge le temps de réaction et augmente fortement le risque d’accident.',
                'answers' => [
                    ['text' => 'Le temps de réaction et le risque d’accident', 'correct' => true],
                    ['text' => 'La précision de conduite', 'correct' => false],
                    ['text' => 'La visibilité de nuit', 'correct' => false],
                    ['text' => 'La distance de freinage disponible', 'correct' => false],
                ],
            ],
        ];

        $categoryQuestionCounters = [];

        foreach ($questions as $questionData) {
            $category = $categories[$questionData['category']];
            $categoryQuestionCounters[$category->slug] = ($categoryQuestionCounters[$category->slug] ?? 0) + 1;
            $sequence = $categoryQuestionCounters[$category->slug];
            $questionType = $this->questionTypeFor($category->slug, $sequence);
            $questionMetadata = $this->questionMetadataFor($questionData, $category, $questionType, $sequence);

            $question = Question::query()->updateOrCreate(
                [
                    'learning_domain_id' => $domain->id,
                    'question_text' => $questionData['question_text'],
                ],
                [
                    'learning_domain_id' => $domain->id,
                    'learning_category_id' => $category->id,
                    'type' => $questionType,
                    'explanation' => $questionData['explanation'],
                    'question_text_translations' => ['fr' => $questionData['question_text']],
                    'explanation_translations' => ['fr' => $questionData['explanation']],
                    'difficulty' => $questionData['difficulty'],
                    'status' => QuestionStatus::Published,
                    'metadata' => $questionMetadata,
                ],
            );

            $question->answers()->delete();

            foreach ($questionData['answers'] as $index => $answerData) {
                $answerMetadata = $this->answerMetadataFor($answerData['text'], $questionType, $index + 1);

                $question->answers()->create([
                    'answer_text' => $answerData['text'],
                    'answer_text_translations' => ['fr' => $answerData['text']],
                    'metadata' => $answerMetadata,
                    'is_correct' => $answerData['correct'],
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    private function questionTypeFor(string $categorySlug, int $sequence): string
    {
        $rotations = [
            'signs' => [
                QuestionType::VisualRecognition,
                QuestionType::ReverseLookup,
                QuestionType::Definition,
                QuestionType::VisualRecognition,
            ],
            'priority' => [
                QuestionType::Situation,
                QuestionType::Association,
                QuestionType::Situation,
                QuestionType::Definition,
            ],
            'speed' => [
                QuestionType::Association,
                QuestionType::Definition,
                QuestionType::ReverseLookup,
                QuestionType::Situation,
            ],
            'safety' => [
                QuestionType::Definition,
                QuestionType::Association,
                QuestionType::Situation,
                QuestionType::Definition,
            ],
            'dangers' => [
                QuestionType::Situation,
                QuestionType::VisualRecognition,
                QuestionType::ReverseLookup,
                QuestionType::Situation,
            ],
        ];

        $rotation = $rotations[$categorySlug] ?? [
            QuestionType::Definition,
            QuestionType::Situation,
        ];

        return $rotation[($sequence - 1) % count($rotation)]->value;
    }

    private function questionMetadataFor(array $questionData, LearningCategory $category, string $questionType, int $sequence): array
    {
        $promptToken = $this->promptTokenFor($questionData['question_text'], $questionData['answers']);

        return array_filter([
            'context' => $questionType === QuestionType::Situation->value ? $questionData['explanation'] : null,
            'hint' => $this->hintFor($questionData['answers']),
            'has_traps' => $questionData['difficulty'] !== QuestionDifficulty::Easy || $sequence % 3 === 0,
            'timer_seconds' => match ($questionType) {
                QuestionType::VisualRecognition->value, QuestionType::ReverseLookup->value => 18,
                QuestionType::Association->value, QuestionType::Definition->value => 22,
                default => 28,
            },
            'visual_token' => in_array($questionType, [QuestionType::VisualRecognition->value, QuestionType::Association->value], true) ? $promptToken : null,
            'prompt_media' => in_array($questionType, [QuestionType::VisualRecognition->value, QuestionType::Association->value], true)
                ? [
                    'kind' => 'tile',
                    'title' => $category->name,
                    'subtitle' => $questionType === QuestionType::Association->value ? 'Associez le bon repère' : 'Repère à reconnaître',
                    'value' => $promptToken,
                    'accent' => $category->color,
                ]
                : null,
            'answer_layout' => $questionType === QuestionType::ReverseLookup->value
                ? 'visual-grid'
                : ($questionType === QuestionType::Association->value ? 'compact-grid' : 'stack'),
        ], fn ($value) => $value !== null);
    }

    private function answerMetadataFor(string $answerText, string $questionType, int $position): array
    {
        return array_filter([
            'badge' => $questionType === QuestionType::ReverseLookup->value
                ? $this->answerBadge($answerText)
                : ($questionType === QuestionType::Association->value ? 'Option '.chr(64 + $position) : null),
            'note' => $questionType === QuestionType::ReverseLookup->value ? 'Repère visuel' : null,
        ], fn ($value) => $value !== null);
    }

    private function promptTokenFor(string $questionText, array $answers): string
    {
        if (preg_match('/\bSTOP\b/u', $questionText)) {
            return 'STOP';
        }

        if (preg_match('/\b\d{2,3}\b/u', $questionText, $matches)) {
            return $matches[0];
        }

        if (Str::contains($questionText, ['droite', 'right'])) {
            return 'RIGHT';
        }

        if (Str::contains($questionText, ['gauche', 'left'])) {
            return 'LEFT';
        }

        if (Str::contains($questionText, ['enfants'])) {
            return 'ALERT';
        }

        $correctAnswer = collect($answers)->firstWhere('correct', true);

        return Str::upper(Str::limit((string) ($correctAnswer['text'] ?? 'REPERE'), 10, ''));
    }

    private function hintFor(array $answers): string
    {
        $correctAnswer = collect($answers)->firstWhere('correct', true);

        return 'Pensez au principe clé : '.Str::limit((string) ($correctAnswer['text'] ?? ''), 60, '...');
    }

    private function answerBadge(string $answerText): string
    {
        if (preg_match('/\b\d{2,3}\b/u', $answerText, $matches)) {
            return $matches[0];
        }

        return Str::upper(Str::limit($answerText, 10, ''));
    }
}
