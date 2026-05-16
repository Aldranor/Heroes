<?php

namespace Tests\Unit\Services;

use App\Enums\CompanionAcquisitionMethod;
use App\Enums\CompanionRole;
use App\Enums\CreatureAcquisitionMethod;
use App\Enums\Currency;
use App\Enums\EnemyType;
use App\Enums\EquipmentTargetType;
use App\Enums\EquipmentType;
use App\Enums\LessonSectionType;
use App\Enums\LessonStatus;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\Rarity;
use App\Enums\ShopItemType;
use App\Enums\SkillTarget;
use App\Enums\SkillType;
use App\Models\Avatar\AvatarItem;
use App\Models\Commerce\Equipment;
use App\Models\Commerce\ShopItem;
use App\Models\Companions\Companion;
use App\Models\Companions\UserCompanion;
use App\Models\Creatures\Creature;
use App\Models\Creatures\Skill;
use App\Models\Creatures\UserCreature;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Learning\LearningTopic;
use App\Models\Learning\Question;
use App\Models\Lessons\Lesson;
use App\Models\Quests\Quest;
use App\Models\User;
use App\Models\World\AdventureDialogue;
use App\Models\World\AdventureMap;
use App\Models\World\AdventureNode;
use App\Models\World\AdventurePath;
use App\Models\World\Enemy;
use App\Models\World\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait CreatesGameData
{
    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create();

        $user->forceFill(array_merge([
            'coins' => 0,
            'xp' => 0,
            'level' => 1,
        ], $attributes))->save();

        return $user->refresh();
    }

    protected function createDomain(array $attributes = []): LearningDomain
    {
        return LearningDomain::query()->create(array_merge([
            'name' => 'Test Domain '.Str::random(6),
            'slug' => 'test-domain-'.Str::random(6),
            'is_active' => true,
        ], $attributes));
    }

    protected function createCategory(LearningDomain $domain, array $attributes = []): LearningCategory
    {
        return LearningCategory::query()->create(array_merge([
            'learning_domain_id' => $domain->id,
            'name' => 'Test Category '.Str::random(6),
            'slug' => 'test-category-'.Str::random(6),
            'is_mixed' => false,
        ], $attributes));
    }

    protected function createTopic(LearningDomain $domain, array $attributes = []): LearningTopic
    {
        return LearningTopic::query()->create(array_merge([
            'learning_domain_id' => $domain->id,
            'title' => 'Test Topic '.Str::random(6),
            'slug' => 'test-topic-'.Str::random(6),
            'description' => 'Topic description',
        ], $attributes));
    }

    protected function createQuestion(
        LearningDomain $domain,
        LearningCategory $category,
        array $attributes = [],
    ): Question {
        $question = Question::query()->create(array_merge([
            'learning_domain_id' => $domain->id,
            'learning_category_id' => $category->id,
            'question_text' => 'Question '.Str::random(6),
            'explanation' => 'Explanation',
            'difficulty' => QuestionDifficulty::Easy,
            'status' => QuestionStatus::Published,
        ], $attributes));

        foreach (range(1, 4) as $index) {
            $question->answers()->create([
                'answer_text' => 'Answer '.$index,
                'is_correct' => $index === 1,
                'sort_order' => $index,
            ]);
        }

        return $question->refresh();
    }

    protected function createLesson(
        LearningDomain $domain,
        ?LearningCategory $category = null,
        array $attributes = [],
        array $questions = [],
    ): Lesson {
        $topic = $attributes['learning_topic'] ?? $this->createTopic($domain);
        unset($attributes['learning_topic']);

        $lesson = Lesson::query()->create(array_merge([
            'learning_domain_id' => $domain->id,
            'learning_topic_id' => $topic->id,
            'learning_category_id' => $category?->id,
            'title' => 'Lesson '.Str::random(6),
            'slug' => 'lesson-'.Str::random(6),
            'summary' => 'Lesson summary',
            'mentor_name' => 'Mentor',
            'mentor_title' => 'Professor',
            'status' => LessonStatus::Published,
            'academic_xp_reward' => 120,
            'quiz_pass_score' => 100,
            'unlocks_training' => true,
            'published_at' => now(),
        ], $attributes));

        $lesson->sections()->create([
            'type' => LessonSectionType::Introduction,
            'title' => 'Introduction',
            'body' => 'Lesson introduction',
            'sort_order' => 1,
        ]);

        foreach (range(1, 2) as $index) {
            $lesson->flashcards()->create([
                'front' => 'Front '.$index,
                'back' => 'Back '.$index,
                'sort_order' => $index,
            ]);
        }

        $lesson->questions()->sync(collect($questions)->mapWithKeys(
            fn (Question $question, int $index): array => [$question->id => ['sort_order' => $index + 1]],
        )->all());

        return $lesson->refresh();
    }

    protected function createSkill(array $attributes = []): Skill
    {
        return Skill::query()->create(array_merge([
            'name' => 'Skill '.Str::random(6),
            'slug' => 'skill-'.Str::random(6),
            'description' => 'Skill description',
            'type' => SkillType::Attack,
            'power' => 15,
            'target' => SkillTarget::Enemy,
            'unlock_level' => 1,
        ], $attributes));
    }

    protected function createCreature(
        LearningDomain $domain,
        ?LearningCategory $category = null,
        array $attributes = [],
    ): Creature {
        $defaultSkill = $attributes['default_skill'] ?? $this->createSkill();
        unset($attributes['default_skill']);

        $creature = Creature::query()->create(array_merge([
            'name' => 'Creature '.Str::random(6),
            'slug' => 'creature-'.Str::random(6),
            'learning_domain_id' => $domain->id,
            'learning_category_id' => $category?->id,
            'rarity' => Rarity::Common,
            'base_hp' => 80,
            'base_attack' => 16,
            'base_defense' => 10,
            'base_speed' => 14,
            'starter_allowed' => true,
            'unlock_level' => 1,
            'acquisition_method' => CreatureAcquisitionMethod::Starter,
            'default_skill_id' => $defaultSkill->id,
        ], $attributes));

        $creature->skills()->syncWithoutDetaching([
            $defaultSkill->id => [
                'unlock_level' => 1,
                'sort_order' => 1,
                'is_default' => true,
            ],
        ]);

        return $creature->refresh();
    }

    protected function createUserCreature(User $user, Creature $creature, array $attributes = []): UserCreature
    {
        return UserCreature::query()->create(array_merge([
            'user_id' => $user->id,
            'creature_id' => $creature->id,
            'level' => 1,
            'xp' => 0,
            'current_hp' => $creature->base_hp,
            'acquired_at' => now(),
        ], $attributes));
    }

    protected function createCompanion(LearningCategory $category, array $attributes = []): Companion
    {
        return Companion::query()->create(array_merge([
            'name' => 'Companion '.Str::random(6),
            'slug' => 'companion-'.Str::random(6),
            'specialization_category_id' => $category->id,
            'role' => CompanionRole::Balanced,
            'rarity' => Rarity::Common,
            'base_hp' => 80,
            'base_attack' => 16,
            'base_defense' => 12,
            'base_speed' => 14,
            'description' => 'Companion description',
            'unlock_level' => 1,
            'acquisition_method' => CompanionAcquisitionMethod::Starter,
        ], $attributes));
    }

    protected function createUserCompanion(User $user, Companion $companion, array $attributes = []): UserCompanion
    {
        return UserCompanion::query()->create(array_merge([
            'user_id' => $user->id,
            'companion_id' => $companion->id,
            'level' => 1,
            'xp' => 0,
            'current_hp' => $companion->base_hp,
            'is_favorite' => false,
        ], $attributes));
    }

    protected function createEnemy(LearningDomain $domain, array $attributes = []): Enemy
    {
        return Enemy::query()->create(array_merge([
            'name' => 'Enemy '.Str::random(6),
            'slug' => 'enemy-'.Str::random(6),
            'type' => EnemyType::Monster,
            'learning_domain_id' => $domain->id,
            'level' => 1,
            'hp' => 40,
            'attack' => 10,
            'defense' => 5,
            'speed' => 8,
            'is_boss' => false,
            'reward_xp' => 30,
            'reward_coins' => 10,
        ], $attributes));
    }

    protected function createWorld(LearningDomain $domain, array $attributes = []): World
    {
        return World::query()->create(array_merge([
            'learning_domain_id' => $domain->id,
            'name' => 'World '.Str::random(6),
            'slug' => 'world-'.Str::random(6),
            'theme' => 'Training Grounds',
            'difficulty' => 'Initiation',
            'description' => 'World description',
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    protected function createAdventureMap(World $world, array $attributes = []): AdventureMap
    {
        return AdventureMap::query()->create(array_merge([
            'world_id' => $world->id,
            'name' => 'Map '.Str::random(6),
            'slug' => 'map-'.Str::random(6),
            'description' => 'Map description',
            'sort_order' => 1,
        ], $attributes));
    }

    protected function createAdventureNode(AdventureMap $map, array $attributes = []): AdventureNode
    {
        return AdventureNode::query()->create(array_merge([
            'adventure_map_id' => $map->id,
            'node_type' => 'combat_normal',
            'title' => 'Node '.Str::random(6),
            'description' => 'Node description',
            'sort_order' => 1,
            'is_start' => false,
            'is_repeatable' => true,
        ], $attributes));
    }

    protected function createAdventurePath(AdventureMap $map, AdventureNode $fromNode, AdventureNode $toNode, array $attributes = []): AdventurePath
    {
        return AdventurePath::query()->create(array_merge([
            'adventure_map_id' => $map->id,
            'from_adventure_node_id' => $fromNode->id,
            'to_adventure_node_id' => $toNode->id,
            'path_type' => 'road',
            'sort_order' => 1,
        ], $attributes));
    }

    protected function createAdventureDialogue(AdventureMap $map, array $attributes = []): AdventureDialogue
    {
        return AdventureDialogue::query()->create(array_merge([
            'adventure_map_id' => $map->id,
            'title' => 'Dialogue '.Str::random(6),
            'trigger_type' => 'map_started',
            'sort_order' => 1,
            'is_active' => true,
            'script' => [
                ['speaker' => 'narrator', 'text' => 'Le vent tourne sur la route.'],
                ['speaker' => 'hero', 'text' => 'Je suis prêt.'],
            ],
        ], $attributes));
    }

    protected function createEquipment(array $attributes = []): Equipment
    {
        return Equipment::query()->create(array_merge([
            'name' => 'Equipment '.Str::random(6),
            'slug' => 'equipment-'.Str::random(6),
            'type' => EquipmentType::Weapon,
            'target_type' => EquipmentTargetType::Creature,
            'rarity' => Rarity::Common,
            'stat_bonus' => ['attack' => 2],
            'price' => 25,
        ], $attributes));
    }

    protected function createAvatarItem(array $attributes = []): AvatarItem
    {
        return AvatarItem::query()->create(array_merge([
            'name' => 'Avatar Item '.Str::random(6),
            'slug' => 'avatar-item-'.Str::random(6),
            'type' => 'accessory',
            'rarity' => Rarity::Common,
            'price' => 20,
            'is_cosmetic_only' => true,
        ], $attributes));
    }

    protected function createShopItem(Model $item, ShopItemType $type, array $attributes = []): ShopItem
    {
        return ShopItem::query()->create(array_merge([
            'item_type' => $type,
            'item_id' => $item->id,
            'price' => 25,
            'currency' => Currency::Coins,
            'is_active' => true,
        ], $attributes));
    }

    protected function createQuest(array $attributes = []): Quest
    {
        return Quest::query()->create(array_merge([
            'title' => 'Quest '.Str::random(6),
            'slug' => 'quest-'.Str::random(6),
            'description' => 'Quest description',
            'type' => 'training',
            'objective_type' => 'complete_training_sessions',
            'objective_target' => 1,
            'reward_xp' => 10,
            'reward_coins' => 15,
            'unlock_level' => 1,
        ], $attributes));
    }
}
