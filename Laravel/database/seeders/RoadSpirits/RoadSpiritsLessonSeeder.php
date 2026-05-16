<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\LessonSectionType;
use App\Enums\LessonStatus;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\LearningTopic;
use App\Models\Lessons\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoadSpiritsLessonSeeder extends Seeder
{
    public function run(): void
    {
        $domain = LearningDomain::query()->where('slug', 'driving_license_be')->firstOrFail();

        LearningCategory::query()
            ->where('learning_domain_id', $domain->id)
            ->where('is_mixed', false)
            ->orderBy('sort_order')
            ->get()
            ->each(function (LearningCategory $category) use ($domain) {
                $topic = LearningTopic::query()->updateOrCreate(
                    [
                        'learning_domain_id' => $domain->id,
                        'slug' => $category->slug,
                    ],
                    [
                        'title' => $category->name,
                        'title_translations' => ['fr' => $category->name],
                        'description' => $category->description ?: "Chapitre de préparation pour les missions liées à {$category->name}.",
                        'description_translations' => ['fr' => $category->description ?: "Chapitre de préparation pour les missions liées à {$category->name}."],
                        'icon' => $category->icon,
                        'color' => $category->color,
                        'sort_order' => $category->sort_order,
                    ],
                );

                $slug = 'academie-heros-'.$category->slug;
                $title = 'Préparation : '.$category->name;

                $lesson = Lesson::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'learning_domain_id' => $domain->id,
                        'learning_topic_id' => $topic->id,
                        'learning_category_id' => $category->id,
                        'title' => $title,
                        'title_translations' => ['fr' => $title],
                        'summary' => "Un module court pour préparer votre héros avant les missions liées à {$category->name}.",
                        'summary_translations' => ['fr' => "Un module court pour préparer votre héros avant les missions liées à {$category->name}."],
                        'mentor_name' => 'Professeur Valen',
                        'mentor_title' => 'Mentor de mission',
                        'hero_image' => 'images/backgrounds/cours.png',
                        'status' => LessonStatus::Published,
                        'difficulty' => $this->difficultyFor($category),
                        'academic_xp_reward' => 120,
                        'quiz_pass_score' => 100,
                        'unlocks_training' => true,
                        'sort_order' => $category->sort_order,
                        'published_at' => now(),
                    ],
                );

                $lesson->sections()->delete();
                $lesson->flashcards()->delete();

                foreach ($this->sectionsFor($category) as $index => $sectionData) {
                    $lesson->sections()->create([
                        'type' => $sectionData['type'],
                        'title' => $sectionData['title'],
                        'title_translations' => ['fr' => $sectionData['title']],
                        'body' => $sectionData['body'],
                        'body_translations' => ['fr' => $sectionData['body']],
                        'image' => $sectionData['image'] ?? null,
                        'sort_order' => $index + 1,
                    ]);
                }

                foreach ($this->flashcardsFor($category) as $index => $flashcardData) {
                    $lesson->flashcards()->create([
                        'front' => $flashcardData['front'],
                        'front_translations' => ['fr' => $flashcardData['front']],
                        'back' => $flashcardData['back'],
                        'back_translations' => ['fr' => $flashcardData['back']],
                        'visual_label' => $flashcardData['visual_label'],
                        'sort_order' => $index + 1,
                    ]);
                }

                $questionIds = $category->questions()
                    ->where('status', QuestionStatus::Published)
                    ->orderBy('id')
                    ->limit(2)
                    ->pluck('id')
                    ->all();

                $lesson->questions()->sync(collect($questionIds)->mapWithKeys(
                    fn (int $questionId, int $index): array => [$questionId => ['sort_order' => $index + 1]],
                )->all());
            });
    }

    private function sectionsFor(LearningCategory $category): array
    {
        return [
            [
                'type' => LessonSectionType::Introduction,
                'title' => 'Briefing',
                'body' => "Ce module prépare votre héros à reconnaître les situations liées à {$category->name}. L’objectif est simple : comprendre, décider, puis agir avec méthode.",
            ],
            [
                'type' => LessonSectionType::KeyConcepts,
                'title' => 'Notions clés',
                'body' => "1. Identifiez l’objectif de la situation.\n2. Repérez les informations utiles.\n3. Écartez les réponses risquées.\n4. Validez la décision la plus sûre.",
            ],
            [
                'type' => LessonSectionType::ConcreteExample,
                'title' => 'Exemple concret',
                'body' => "Avant une mission {$category->name}, prenez le temps de lire l’énoncé, de comparer les options et de justifier votre choix. La validation immédiate indiquera quoi renforcer.",
                'image' => 'images/backgrounds/training_center.png',
            ],
            [
                'type' => LessonSectionType::Summary,
                'title' => 'Résumé',
                'body' => 'Un héros efficace ne répond pas au hasard. Il observe, applique le principe adapté et progresse à chaque correction.',
            ],
        ];
    }

    private function flashcardsFor(LearningCategory $category): array
    {
        $label = Str::upper(Str::limit($category->name, 10, ''));

        return [
            [
                'visual_label' => $label.' 01',
                'front' => 'Avant de répondre',
                'back' => 'Repérez le thème exact de la situation et l’objectif attendu.',
            ],
            [
                'visual_label' => $label.' 02',
                'front' => 'Pendant l’analyse',
                'back' => 'Comparez les options et éliminez celles qui créent un risque évident.',
            ],
            [
                'visual_label' => $label.' 03',
                'front' => 'Après la correction',
                'back' => 'Notez le principe à retenir pour la prochaine mission.',
            ],
        ];
    }

    private function difficultyFor(LearningCategory $category): QuestionDifficulty
    {
        return match ($category->slug) {
            'priority', 'speed' => QuestionDifficulty::Medium,
            'dangers' => QuestionDifficulty::Hard,
            default => QuestionDifficulty::Easy,
        };
    }
}
