<?php

namespace App\Services;

use App\Enums\AdventureDialogueTrigger;
use App\Enums\AdventureNodeProgressStatus;
use App\Models\Learning\LearningCategory;
use App\Models\Learning\LearningDomain;
use App\Models\Lessons\Lesson;
use App\Models\Training\TrainingSession;
use App\Models\Battle\Battle;
use App\Models\User;
use App\Models\World\AdventureDialogue;
use App\Models\World\AdventureMap;
use App\Models\World\AdventureNode;
use App\Models\World\AdventurePath;
use App\Models\World\UserAdventureNodeProgress;
use App\Models\World\World;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdventureMapService
{
    public function missionBoard(
        User $user,
        EloquentCollection $domains,
        ?int $selectedWorldId = null,
        ?int $selectedMapId = null,
        ?int $selectedNodeId = null,
    ): array
    {
        if (! Schema::hasTable('worlds')) {
            return [
                'worlds' => collect(),
                'selectedWorld' => null,
                'selectedMap' => null,
                'selectedNode' => null,
                'selectedDialogue' => null,
            ];
        }

        $this->ensureGeneratedWorlds($domains);

        $worlds = World::query()
            ->where('is_active', true)
            ->with([
                'learningDomain',
                'introScenario.steps.npc',
                'maps' => fn ($query) => $query->orderBy('sort_order'),
                'maps.lesson',
                'maps.introScenario.steps.npc',
                'maps.dialogues',
                'maps.nodes' => fn ($query) => $query->orderBy('sort_order'),
                'maps.nodes.lesson',
                'maps.nodes.learningCategory',
                'maps.nodes.scenario.steps.npc',
                'maps.nodes.incomingPaths',
                'maps.nodes.outgoingPaths',
                'maps.paths',
            ])
            ->orderBy('sort_order')
            ->get();

        $worlds = $this->decorateWorlds($user, $worlds);
        $selectedWorld = $worlds->firstWhere('id', $selectedWorldId)
            ?? $worlds->firstWhere('ui_has_selected_node', true)
            ?? $worlds->first();
        $selectedMap = $selectedWorld?->maps->firstWhere('id', $selectedMapId)
            ?? $selectedWorld?->maps->firstWhere('ui_has_selected_node', true)
            ?? $selectedWorld?->maps->firstWhere('ui_has_available_node', true)
            ?? $selectedWorld?->maps->first();

        $selectedNode = null;

        if ($selectedMap) {
            $selectedNode = $selectedMap->nodes->firstWhere('id', $selectedNodeId)
                ?? $selectedMap->nodes->firstWhere('ui_is_recommended', true)
                ?? $selectedMap->nodes->firstWhere('ui_status', AdventureNodeProgressStatus::Available->value)
                ?? $selectedMap->nodes->first();
        }

        if ($selectedNode && $selectedNode->getAttribute('ui_status') === AdventureNodeProgressStatus::Locked->value) {
            $selectedNode = $selectedMap?->nodes->firstWhere('ui_status', AdventureNodeProgressStatus::Available->value) ?? $selectedNode;
        }

        if ($selectedNode) {
            $selectedMap?->setAttribute('ui_has_selected_node', true);
            $selectedWorld?->setAttribute('ui_has_selected_node', true);
        }

        $selectedDialogue = $selectedMap
            ? $this->dialogueForSelection($selectedMap, $selectedNode)
            : null;

        return compact('worlds', 'selectedWorld', 'selectedMap', 'selectedNode', 'selectedDialogue');
    }

    public function nodeForSelection(?int $nodeId): ?AdventureNode
    {
        if (! $nodeId || ! Schema::hasTable('adventure_nodes')) {
            return null;
        }

        return AdventureNode::query()
            ->with(['map.world.learningDomain', 'learningCategory', 'lesson'])
            ->find($nodeId);
    }

    public function assertNodeAvailableForUser(User $user, AdventureNode $node): void
    {
        $status = $this->nodeStatusMap($user, $node->map->nodes()->with('incomingPaths')->orderBy('sort_order')->get())->get($node->id);

        abort_unless($status !== AdventureNodeProgressStatus::Locked, 422, 'Cette mission est encore verrouillée.');
    }

    public function markMissionStarted(User $user, AdventureNode $node, TrainingSession $trainingSession): void
    {
        $progress = UserAdventureNodeProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'adventure_node_id' => $node->id,
        ]);

        $progress->status = $progress->status === AdventureNodeProgressStatus::Completed
            ? AdventureNodeProgressStatus::Completed
            : AdventureNodeProgressStatus::Available;
        $progress->attempts = (int) $progress->attempts + 1;
        $progress->metadata = array_merge($progress->metadata ?? [], [
            'last_training_session_id' => $trainingSession->id,
        ]);
        $progress->save();
    }

    public function markMissionBattleStarted(User $user, AdventureNode $node, Battle $battle): void
    {
        $progress = UserAdventureNodeProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'adventure_node_id' => $node->id,
        ]);

        $progress->status = $progress->status === AdventureNodeProgressStatus::Completed
            ? AdventureNodeProgressStatus::Completed
            : AdventureNodeProgressStatus::Available;
        $progress->attempts = (int) $progress->attempts + 1;
        $progress->metadata = array_merge($progress->metadata ?? [], [
            'last_battle_id' => $battle->id,
        ]);
        $progress->save();
    }

    public function markMissionCompleted(User $user, AdventureNode $node, TrainingSession $trainingSession): void
    {
        DB::transaction(function () use ($user, $node, $trainingSession): void {
            UserAdventureNodeProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'adventure_node_id' => $node->id,
                ],
                [
                    'status' => AdventureNodeProgressStatus::Completed,
                    'completed_at' => now(),
                    'metadata' => [
                        'last_training_session_id' => $trainingSession->id,
                    ],
                ],
            );

            $node->loadMissing('outgoingPaths');

            foreach ($node->outgoingPaths as $path) {
                UserAdventureNodeProgress::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'adventure_node_id' => $path->to_adventure_node_id,
                    ],
                    [
                        'status' => AdventureNodeProgressStatus::Available,
                    ],
                );
            }
        });
    }

    public function markMissionBattleCompleted(User $user, AdventureNode $node, Battle $battle): void
    {
        DB::transaction(function () use ($user, $node, $battle): void {
            UserAdventureNodeProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'adventure_node_id' => $node->id,
                ],
                [
                    'status' => AdventureNodeProgressStatus::Completed,
                    'completed_at' => now(),
                    'metadata' => [
                        'last_battle_id' => $battle->id,
                    ],
                ],
            );

            $node->loadMissing('outgoingPaths');

            foreach ($node->outgoingPaths as $path) {
                UserAdventureNodeProgress::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'adventure_node_id' => $path->to_adventure_node_id,
                    ],
                    [
                        'status' => AdventureNodeProgressStatus::Available,
                    ],
                );
            }
        });
    }

    protected function decorateWorlds(User $user, EloquentCollection $worlds): EloquentCollection
    {
        $progressMap = UserAdventureNodeProgress::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('adventure_node_id');

        $firstRecommendedNodeMarked = false;

        foreach ($worlds as $world) {
            $worldCompleted = 0;
            $worldTotal = 0;
            $world->setAttribute('ui_has_selected_node', false);
            $legacySlug = 'map-'.$world->learningDomain?->slug;

            if ($legacySlug) {
                $world->setRelation('maps', $world->maps
                    ->reject(fn (AdventureMap $map) => $map->slug === $legacySlug && $world->maps->count() > 1)
                    ->values());
            }

            foreach ($world->maps as $map) {
                $mapStatuses = $this->nodeStatusMap($user, $map->nodes, $progressMap);
                $map->setAttribute('ui_has_selected_node', false);
                $mapCompleted = 0;
                $mapTotal = $map->nodes->count();
                $mapHasAvailableNode = false;

                foreach ($map->nodes as $node) {
                    $definition = config("adventure.node_types.{$node->node_type}", []);
                    $status = $mapStatuses->get($node->id, AdventureNodeProgressStatus::Locked);
                    $isRecommended = ! $firstRecommendedNodeMarked && $status === AdventureNodeProgressStatus::Available;

                    $node->setAttribute('ui_status', $status->value);
                    $node->setAttribute('ui_label', $definition['label'] ?? ucfirst(str_replace('_', ' ', $node->node_type)));
                    $node->setAttribute('ui_badge', $definition['badge'] ?? 'Mission');
                    $node->setAttribute('ui_tone', $definition['tone'] ?? 'stone');
                    $node->setAttribute('ui_icon', $definition['icon'] ?? 'swords');
                    $node->setAttribute('ui_is_recommended', $isRecommended);
                    $node->setAttribute('ui_dialogue_steps', $node->scenario?->steps ?? collect());
                    $node->setAttribute('ui_lesson_url', $node->lesson ? route('lessons.show', $node->lesson) : null);

                    if ($isRecommended) {
                        $firstRecommendedNodeMarked = true;
                    }

                    $worldTotal++;
                    if ($status === AdventureNodeProgressStatus::Completed) {
                        $worldCompleted++;
                        $mapCompleted++;
                    }
                    if ($status === AdventureNodeProgressStatus::Available) {
                        $mapHasAvailableNode = true;
                    }
                }

                $map->setAttribute('ui_progress_percent', $mapTotal > 0 ? (int) round(($mapCompleted / $mapTotal) * 100) : 0);
                $map->setAttribute('ui_is_completed', $mapTotal > 0 && $mapCompleted === $mapTotal);
                $map->setAttribute('ui_has_available_node', $mapHasAvailableNode);
                $map->setAttribute('ui_intro_dialogue', $map->dialogues
                    ->first(fn (AdventureDialogue $dialogue) => $dialogue->trigger_type === AdventureDialogueTrigger::MapStarted));
            }

            $world->setAttribute('ui_progress_percent', $worldTotal > 0 ? (int) round(($worldCompleted / $worldTotal) * 100) : 0);
            $world->setAttribute('ui_background_image', $world->background_image
                ? asset($world->background_image)
                : ($world->maps->first()?->background_image
                    ? asset($world->maps->first()->background_image)
                    : asset($this->defaultBackgroundForWorld($world->sort_order))));
            $world->setAttribute('ui_dialogue_steps', $world->introScenario?->steps ?? collect());
        }

        return $worlds;
    }

    protected function nodeStatusMap(User $user, Collection $nodes, ?Collection $progressMap = null): Collection
    {
        $progressMap ??= UserAdventureNodeProgress::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('adventure_node_id');

        $statuses = collect();

        foreach ($nodes->sortBy('sort_order') as $node) {
            $progress = $progressMap->get($node->id);

            if ($progress?->status === AdventureNodeProgressStatus::Completed) {
                $statuses[$node->id] = AdventureNodeProgressStatus::Completed;
                continue;
            }

            $incomingNodeIds = $node->incomingPaths->pluck('from_adventure_node_id')->filter()->values();

            if ($incomingNodeIds->isEmpty()) {
                $statuses[$node->id] = AdventureNodeProgressStatus::Available;
                continue;
            }

            $allParentsComplete = $incomingNodeIds->every(fn ($parentId) => $statuses->get($parentId) === AdventureNodeProgressStatus::Completed
                || $progressMap->get($parentId)?->status === AdventureNodeProgressStatus::Completed);

            $statuses[$node->id] = $allParentsComplete
                ? AdventureNodeProgressStatus::Available
                : AdventureNodeProgressStatus::Locked;
        }

        return $statuses;
    }

    protected function ensureGeneratedWorlds(EloquentCollection $domains): void
    {
        $backgrounds = config('adventure.default_backgrounds', []);

        foreach ($domains as $domainIndex => $domain) {
            if ($domain->categories->isEmpty()) {
                continue;
            }

            $world = World::query()->firstOrCreate(
                ['slug' => 'world-'.$domain->slug],
                [
                    'learning_domain_id' => $domain->id,
                    'name' => $domain->name,
                    'theme' => $domain->name,
                    'difficulty' => $this->difficultyForIndex($domainIndex),
                    'description' => "Zone d'apprentissage consacrée à {$domain->name}.",
                    'background_image' => $backgrounds[$domainIndex % max(count($backgrounds), 1)] ?? null,
                    'sort_order' => $domainIndex + 1,
                ],
            );

            foreach ($domain->categories->values() as $categoryIndex => $category) {
                $lesson = $this->lessonForCategory($domain, $category);
                $map = AdventureMap::query()->firstOrCreate(
                    ['slug' => 'map-'.$domain->slug.'-'.$category->slug],
                    [
                        'world_id' => $world->id,
                        'learning_category_id' => $category->id,
                        'lesson_id' => $lesson?->id,
                        'name' => $lesson?->title ?: $category->name,
                        'description' => $lesson?->summary ?: "Mission de maîtrise sur {$category->name}.",
                        'background_image' => $world->background_image,
                        'sort_order' => $categoryIndex + 1,
                    ],
                );

                $nodeBlueprints = [
                    ['node_type' => 'combat_normal', 'title' => 'Avant-poste', 'subtitle' => 'Premier affrontement', 'training_mode' => 'mixed'],
                    ['node_type' => 'combat_elite', 'title' => 'Garde d’élite', 'subtitle' => 'Pression maximale', 'training_mode' => 'mixed'],
                    ['node_type' => 'boss', 'title' => 'Boss de mission', 'subtitle' => 'Combat final', 'training_mode' => 'mixed'],
                ];

                $previousNode = null;

                if ($map->nodes()->count() === 0) {
                    foreach ($nodeBlueprints as $nodeIndex => $blueprint) {
                        $node = AdventureNode::query()->create([
                            'adventure_map_id' => $map->id,
                            'learning_category_id' => $category->id,
                            'lesson_id' => $lesson?->id,
                            'node_type' => $blueprint['node_type'],
                            'training_mode' => $blueprint['training_mode'],
                            'title' => $blueprint['title'],
                            'subtitle' => $blueprint['subtitle'],
                            'description' => $lesson?->summary ?? "Mission de maîtrise sur {$category->name}.",
                            'sort_order' => $nodeIndex + 1,
                            'is_start' => $nodeIndex === 0,
                            'metadata' => [
                                'generated' => true,
                            ],
                        ]);

                        if ($previousNode) {
                            AdventurePath::query()->create([
                                'adventure_map_id' => $map->id,
                                'from_adventure_node_id' => $previousNode->id,
                                'to_adventure_node_id' => $node->id,
                                'sort_order' => $nodeIndex,
                            ]);
                        }

                        $previousNode = $node;
                    }
                }

                $bossNode = $map->nodes()->orderByDesc('sort_order')->first();

                if ($map->dialogues()->count() === 0) {
                    AdventureDialogue::query()->create([
                        'adventure_map_id' => $map->id,
                        'title' => 'Ouverture de mission',
                        'trigger_type' => AdventureDialogueTrigger::MapStarted,
                        'sort_order' => 1,
                        'script' => [
                            ['speaker' => 'narrator', 'text' => "Une nouvelle mission s’ouvre dans {$domain->name}."],
                            ['speaker' => 'npc', 'name' => 'Mentor', 'text' => "Héros, sécurise {$category->name} et ouvre la route jusqu’au boss."],
                            ['speaker' => 'hero', 'text' => 'Compris. Je prends la tête de l’escouade.'],
                        ],
                    ]);

                    AdventureDialogue::query()->create([
                        'adventure_map_id' => $map->id,
                        'title' => 'Victoire de mission',
                        'trigger_type' => AdventureDialogueTrigger::MapCompleted,
                        'sort_order' => 2,
                        'script' => [
                            ['speaker' => 'npc', 'name' => 'Mentor', 'text' => "Mission accomplie. {$category->name} est désormais sous contrôle."],
                            ['speaker' => 'hero', 'text' => 'La route est sécurisée. On peut avancer vers la zone suivante.'],
                        ],
                    ]);

                    if ($bossNode) {
                        AdventureDialogue::query()->create([
                            'adventure_map_id' => $map->id,
                            'adventure_node_id' => $bossNode->id,
                            'title' => 'Boss vaincu',
                            'trigger_type' => AdventureDialogueTrigger::NodeCompleted,
                            'sort_order' => 3,
                            'script' => [
                                ['speaker' => 'npc', 'name' => 'Mentor', 'text' => 'Le boss tombe. La mission est presque terminée.'],
                                ['speaker' => 'hero', 'text' => 'On maintient la ligne et on clôt la carte.'],
                            ],
                        ]);
                    }
                }
            }
        }
    }

    protected function dialogueForSelection(AdventureMap $map, ?AdventureNode $selectedNode): ?AdventureDialogue
    {
        $selectedNodeCompleted = $selectedNode?->getAttribute('ui_status') === AdventureNodeProgressStatus::Completed->value;

        if ($selectedNodeCompleted) {
            $nodeDialogue = $map->dialogues
                ->first(fn (AdventureDialogue $dialogue) => $dialogue->trigger_type === AdventureDialogueTrigger::NodeCompleted
                    && $dialogue->adventure_node_id === $selectedNode?->id);

            if ($nodeDialogue) {
                return $nodeDialogue;
            }
        }

        if ($map->getAttribute('ui_is_completed')) {
            $completionDialogue = $map->dialogues
                ->first(fn (AdventureDialogue $dialogue) => $dialogue->trigger_type === AdventureDialogueTrigger::MapCompleted);

            if ($completionDialogue) {
                return $completionDialogue;
            }
        }

        return $map->dialogues
            ->first(fn (AdventureDialogue $dialogue) => $dialogue->trigger_type === AdventureDialogueTrigger::MapStarted);
    }

    protected function lessonForCategory(LearningDomain $domain, ?LearningCategory $category): ?Lesson
    {
        if (! $category) {
            return null;
        }

        return Lesson::query()
            ->where('learning_domain_id', $domain->id)
            ->where('learning_category_id', $category->id)
            ->latest('published_at')
            ->first();
    }

    protected function difficultyForIndex(int $index): string
    {
        return ['Initiation', 'Escalade', 'Maîtrise', 'Légende'][$index % 4];
    }

    protected function defaultBackgroundForWorld(int $sortOrder): string
    {
        $backgrounds = config('adventure.default_backgrounds', ['images/backgrounds/training.png']);

        return $backgrounds[max(0, ($sortOrder - 1) % count($backgrounds))];
    }
}
