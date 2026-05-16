<?php

namespace App\Services;

use App\Enums\StorySceneType;
use App\Models\User;
use App\Models\World\AdventureDialogue;
use App\Models\World\Scenario;
use App\Models\World\UserStorySceneProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StorySceneService
{
    public function missionSceneState(User $user, ?AdventureDialogue $dialogue, ?Scenario $scenario = null): ?array
    {
        if ($scenario && $scenario->steps->isNotEmpty()) {
            $lines = $this->scenarioLines($scenario);

            return [
                'scene_type' => StorySceneType::Scenario->value,
                'scene_id' => $scenario->id,
                'title' => $scenario->title,
                'lines' => $lines,
                'cast' => $this->castFromLines($lines),
                'completed' => $this->isCompleted($user, StorySceneType::Scenario, $scenario->id),
            ];
        }

        if ($dialogue && ! empty($dialogue->script)) {
            $lines = $this->dialogueLines($dialogue);

            return [
                'scene_type' => StorySceneType::AdventureDialogue->value,
                'scene_id' => $dialogue->id,
                'title' => $dialogue->title,
                'lines' => $lines,
                'cast' => $this->castFromLines($lines),
                'completed' => $this->isCompleted($user, StorySceneType::AdventureDialogue, $dialogue->id),
            ];
        }

        return null;
    }

    public function markCompleted(User $user, string $sceneType, int $sceneId): void
    {
        $type = StorySceneType::from($sceneType);

        if (! $this->sceneExists($type, $sceneId)) {
            abort(404);
        }

        UserStorySceneProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'scene_type' => $type,
                'scene_id' => $sceneId,
            ],
            [
                'first_seen_at' => now(),
                'completed_at' => now(),
            ],
        );
    }

    public function library(User $user, ?string $selectedSceneType = null, ?int $selectedSceneId = null): array
    {
        $progressEntries = UserStorySceneProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get();

        $dialogueIds = $progressEntries
            ->where('scene_type', StorySceneType::AdventureDialogue)
            ->pluck('scene_id')
            ->all();
        $scenarioIds = $progressEntries
            ->where('scene_type', StorySceneType::Scenario)
            ->pluck('scene_id')
            ->all();

        $dialogues = AdventureDialogue::query()
            ->with(['map.world', 'node'])
            ->whereIn('id', $dialogueIds)
            ->get()
            ->keyBy('id');
        $scenarios = Scenario::query()
            ->with(['learningDomain', 'steps.npc'])
            ->whereIn('id', $scenarioIds)
            ->get()
            ->keyBy('id');

        $memories = $progressEntries
            ->map(function (UserStorySceneProgress $progress) use ($dialogues, $scenarios) {
                if ($progress->scene_type === StorySceneType::AdventureDialogue) {
                    $dialogue = $dialogues->get($progress->scene_id);

                    if (! $dialogue) {
                        return null;
                    }

                    $lines = $this->dialogueLines($dialogue);

                    return [
                        'scene_type' => StorySceneType::AdventureDialogue->value,
                        'scene_id' => $dialogue->id,
                        'title' => $dialogue->title,
                        'source' => trim(collect([$dialogue->map?->world?->name, $dialogue->map?->name])->filter()->implode(' · ')),
                        'excerpt' => Str::limit((string) ($lines->first()['text'] ?? ''), 120),
                        'completed_at' => $progress->completed_at,
                    ];
                }

                $scenario = $scenarios->get($progress->scene_id);

                if (! $scenario) {
                    return null;
                }

                $lines = $this->scenarioLines($scenario);

                return [
                    'scene_type' => StorySceneType::Scenario->value,
                    'scene_id' => $scenario->id,
                    'title' => $scenario->title,
                    'source' => $scenario->learningDomain?->name ?? 'Archives',
                    'excerpt' => Str::limit((string) ($lines->first()['text'] ?? ''), 120),
                    'completed_at' => $progress->completed_at,
                ];
            })
            ->filter()
            ->values();

        $selectedMemory = $memories->first(fn (array $memory) => $memory['scene_type'] === $selectedSceneType && $memory['scene_id'] === $selectedSceneId)
            ?? $memories->first();

        $selectedScene = null;

        if ($selectedMemory) {
            $selectedScene = $selectedMemory['scene_type'] === StorySceneType::AdventureDialogue->value
                ? $this->missionSceneState($user, $dialogues->get($selectedMemory['scene_id']), null)
                : $this->missionSceneState($user, null, $scenarios->get($selectedMemory['scene_id']));
        }

        return [
            'memoryScenes' => $memories,
            'selectedMemoryScene' => $selectedScene,
            'selectedMemoryMeta' => $selectedMemory,
        ];
    }

    protected function isCompleted(User $user, StorySceneType $type, int $sceneId): bool
    {
        return UserStorySceneProgress::query()
            ->where('user_id', $user->id)
            ->where('scene_type', $type)
            ->where('scene_id', $sceneId)
            ->whereNotNull('completed_at')
            ->exists();
    }

    protected function sceneExists(StorySceneType $type, int $sceneId): bool
    {
        return match ($type) {
            StorySceneType::AdventureDialogue => AdventureDialogue::query()->whereKey($sceneId)->exists(),
            StorySceneType::Scenario => Scenario::query()->whereKey($sceneId)->exists(),
        };
    }

    protected function castFromLines(Collection $lines): Collection
    {
        return $lines
            ->map(fn (array $line) => [
                'speaker' => $line['speaker'],
                'name' => $line['speaker'] === 'hero'
                    ? 'Héros'
                    : ($line['name'] ?? ($line['speaker'] === 'narrator' ? 'Narration' : 'Allié')),
            ])
            ->unique(fn (array $line) => $line['speaker'].'-'.$line['name'])
            ->values();
    }

    protected function scenarioLines(Scenario $scenario): Collection
    {
        return $scenario->steps->map(function ($step) {
            $speakerName = $step->npc?->name ?: ($step->speaker_name ?: 'Narration');
            $speakerKey = strtolower((string) $speakerName) === 'hero'
                ? 'hero'
                : (strtolower((string) $speakerName) === 'narration' ? 'narrator' : ($step->npc ? 'npc' : 'npc'));

            return [
                'speaker' => $speakerKey,
                'name' => $speakerName,
                'text' => $step->text,
                'portrait' => $this->resolvePortraitUrl($step->portrait ?: $step->npc?->portrait),
                'initials' => $this->initialsForName($speakerName),
            ];
        })->values();
    }

    protected function dialogueLines(AdventureDialogue $dialogue): Collection
    {
        return collect($dialogue->script ?? [])
            ->map(function (array $line) {
                $speakerName = $line['speaker'] === 'hero'
                    ? 'Héros'
                    : ($line['name'] ?? ($line['speaker'] === 'narrator' ? 'Narration' : 'Allié'));

                return [
                    'speaker' => $line['speaker'] ?? 'narrator',
                    'name' => $line['name'] ?? null,
                    'text' => $line['text'] ?? '',
                    'portrait' => $this->resolvePortraitUrl($line['portrait'] ?? null),
                    'initials' => $this->initialsForName($speakerName),
                ];
            })
            ->filter(fn (array $line) => filled($line['text']))
            ->values();
    }

    protected function resolvePortraitUrl(?string $portraitPath): ?string
    {
        if (! filled($portraitPath)) {
            return null;
        }

        if (str_starts_with($portraitPath, 'http://') || str_starts_with($portraitPath, 'https://') || str_starts_with($portraitPath, '/')) {
            return $portraitPath;
        }

        return asset($portraitPath);
    }

    protected function initialsForName(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'PN';
    }
}
