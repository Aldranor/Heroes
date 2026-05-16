@php
    $avatarAssets = app(\App\Support\AvatarAssetCatalog::class);
    $playerAvatar = auth()->user()?->loadMissing('avatar')?->avatar;
    $avatarPresetKey = data_get($playerAvatar?->equipped_items, 'outfit_preset', data_get($playerAvatar?->equipped_items, 'outfit', $playerAvatar?->style));
    $avatarBodyKey = data_get($playerAvatar?->equipped_items, 'body', config('avatar.defaults.body'));
    $avatarHairKey = data_get($playerAvatar?->equipped_items, 'hair', config('avatar.defaults.hair'));
    $avatarBodyConfig = $avatarAssets->bodyAsset($avatarBodyKey) ?? [];
    $avatarHairConfig = $avatarAssets->hairAsset($avatarHairKey) ?? [];
    $maxCombatCompanions = (int) config('combat.party.max_companions', 2);
    $selectedCompanionIds = collect(old('user_companion_ids', $companions->take($maxCombatCompanions)->pluck('id')->all()))
        ->filter(fn ($id) => is_numeric($id))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
    $combatNodeSelected = in_array($selectedNode?->node_type, ['combat_normal', 'combat_elite', 'boss'], true);
    $missionLocked = ! $selectedNode
        || $selectedNode->getAttribute('ui_status') === 'locked'
        || ! $combatNodeSelected;

    $missionArtSet = [
        asset('images/backgrounds/mission_valley_path.png'),
        asset('images/backgrounds/mission_forest_path.png'),
    ];
    $missionArtIndex = max(0, ((int) ($selectedWorld?->sort_order ?? 1)) - 1) % count($missionArtSet);
    $heroIllustration = $missionArtSet[$missionArtIndex];
    $mapIllustration = $missionArtSet[($missionArtIndex + 1) % count($missionArtSet)];

    $nodes = $selectedMap?->nodes?->values() ?? collect();
    $nodeCount = max($nodes->count(), 1);
    $mapHeight = max(680, 260 + ($nodes->count() * 172));
    $lanePattern = [50, 74, 34, 68, 38, 76, 30, 64, 44, 72, 28, 58];
    $startY = 90;
    $endY = 10;
    $travelDistance = $startY - $endY;

    $statusPalette = [
        'completed' => [
            'surface' => 'linear-gradient(180deg, #86efac 0%, #22c55e 100%)',
            'ink' => '#052e16',
            'ring' => '#dcfce7',
            'shadow' => '0 18px 28px rgba(20, 83, 45, 0.38)',
        ],
        'available' => [
            'surface' => 'linear-gradient(180deg, #fef3c7 0%, #f59e0b 100%)',
            'ink' => '#451a03',
            'ring' => '#fff7d6',
            'shadow' => '0 20px 28px rgba(180, 83, 9, 0.34)',
        ],
        'locked' => [
            'surface' => 'linear-gradient(180deg, #78716c 0%, #44403c 100%)',
            'ink' => '#f5f5f4',
            'ring' => 'rgba(255, 255, 255, 0.22)',
            'shadow' => '0 18px 28px rgba(15, 23, 42, 0.28)',
        ],
    ];

    $typePalette = [
        'combat_normal' => ['size' => 74, 'radius' => '999px', 'plate' => '#166534', 'label' => 'Monstre', 'tag' => 'Combat'],
        'combat_elite' => ['size' => 86, 'radius' => '28px', 'plate' => '#92400e', 'label' => 'Elite', 'tag' => 'Elite'],
        'boss' => ['size' => 108, 'radius' => '34px', 'plate' => '#9f1239', 'label' => 'Boss', 'tag' => 'Boss'],
        'treasure' => ['size' => 78, 'radius' => '26px', 'plate' => '#047857', 'label' => 'Trésor', 'tag' => 'Trésor'],
        'checkpoint' => ['size' => 74, 'radius' => '999px', 'plate' => '#0369a1', 'label' => 'Relais', 'tag' => 'Relais'],
        'event' => ['size' => 78, 'radius' => '26px', 'plate' => '#6d28d9', 'label' => 'Scène', 'tag' => 'Dialogue'],
        'shop' => ['size' => 78, 'radius' => '26px', 'plate' => '#9a3412', 'label' => 'Boutique', 'tag' => 'Boutique'],
        'rest' => ['size' => 76, 'radius' => '999px', 'plate' => '#0f766e', 'label' => 'Repos', 'tag' => 'Repos'],
    ];

    $nodeMarkers = $nodes->map(function ($node, $index) use ($nodeCount, $lanePattern, $startY, $travelDistance, $statusPalette, $typePalette) {
        $metadata = $node->metadata ?? [];
        $statusKey = $node->getAttribute('ui_status') ?? 'locked';
        $typeStyle = $typePalette[$node->node_type] ?? $typePalette['combat_normal'];
        $statusStyle = $statusPalette[$statusKey] ?? $statusPalette['locked'];
        $x = is_numeric($metadata['map_x'] ?? null)
            ? max(20, min(80, (float) $metadata['map_x']))
            : $lanePattern[$index % count($lanePattern)];
        $y = is_numeric($metadata['map_y'] ?? null)
            ? max(8, min(92, (float) $metadata['map_y']))
            : ($nodeCount === 1 ? 50 : $startY - (($travelDistance / max($nodeCount - 1, 1)) * $index));

        return [
            'node' => $node,
            'index' => $index + 1,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'size' => $typeStyle['size'],
            'radius' => $typeStyle['radius'],
            'plate' => $typeStyle['plate'],
            'label' => $typeStyle['label'],
            'tag' => $typeStyle['tag'],
            'status_key' => $statusKey,
            'surface' => $statusStyle['surface'],
            'ink' => $statusStyle['ink'],
            'ring' => $statusStyle['ring'],
            'shadow' => $statusStyle['shadow'],
            'stars' => match ($statusKey) {
                'completed' => 3,
                'available' => 1,
                default => 0,
            },
        ];
    });

    $pathPoints = $nodeMarkers->map(fn ($marker) => ['x' => $marker['x'], 'y' => $marker['y']])->values()->all();
    $pathD = '';

    foreach ($pathPoints as $pointIndex => $point) {
        if ($pointIndex === 0) {
            $pathD = 'M '.$point['x'].' '.$point['y'];
            continue;
        }

        $previousPoint = $pathPoints[$pointIndex - 1];
        $controlY = round(($previousPoint['y'] + $point['y']) / 2, 2);
        $pathD .= ' C '.$previousPoint['x'].' '.$controlY.', '.$point['x'].' '.$controlY.', '.$point['x'].' '.$point['y'];
    }

    $selectedScene = $selectedScene ?? null;
    $conversationLines = collect($selectedScene['lines'] ?? []);

    $launchLabel = match ($selectedNode?->node_type) {
        'boss' => 'Affronter le boss',
        'combat_elite' => 'Défier l\'élite',
        'event' => 'Jouer la scène',
        'rest' => 'Continuer',
        'shop' => 'Continuer',
        'treasure' => 'Ouvrir le nœud',
        default => 'Lancer le combat',
    };

    $selectedStatusLabel = match ($selectedNode?->getAttribute('ui_status')) {
        'completed' => 'Déjà vaincu',
        'available' => 'Prêt',
        default => 'Verrouillé',
    };

    $sceneTitle = $selectedScene['title'] ?? ($selectedNode ? 'Scène' : 'Dialogue');
    $sceneCast = collect($selectedScene['cast'] ?? []);
    $sceneStartsFinished = $errors->any() || $conversationLines->isEmpty() || ($selectedScene['completed'] ?? false);
    $sceneFinishUrl = $selectedScene
        ? route('missions.index', array_filter([
            'world' => $selectedWorld?->id,
            'map' => $selectedMap?->id,
            'node' => $selectedNode?->id,
            'complete_scene_type' => $selectedScene['scene_type'] ?? null,
            'complete_scene_id' => $selectedScene['scene_id'] ?? null,
        ]))
        : null;
@endphp

<style>
    [x-cloak] {
        display: none !important;
    }

    .mission-route-scroll {
        scrollbar-width: none;
    }

    .mission-route-scroll::-webkit-scrollbar {
        display: none;
    }

    .mission-node-bubble {
        position: relative;
    }

    .mission-node-bubble::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -8px;
        width: 14px;
        height: 14px;
        transform: translateX(-50%) rotate(45deg);
        background: rgba(12, 10, 9, 0.88);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .mission-dialogue-shell {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background:
            linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(12, 10, 9, 0.94) 100%);
        box-shadow: 0 20px 40px rgba(12, 10, 9, 0.18);
    }

    .mission-rpg-window {
        position: relative;
        overflow: hidden;
        border: 3px solid rgba(248, 250, 252, 0.88);
        background:
            linear-gradient(180deg, rgba(8, 15, 33, 0.96) 0%, rgba(19, 32, 55, 0.98) 100%);
        box-shadow:
            0 0 0 3px rgba(15, 23, 42, 0.95),
            0 20px 36px rgba(2, 6, 23, 0.4);
    }

    .mission-rpg-window::before {
        content: '';
        position: absolute;
        inset: 10px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 1rem;
        pointer-events: none;
    }

    .mission-hero-portrait {
        position: relative;
        width: 4.9rem;
        aspect-ratio: 1 / 1.05;
        overflow: hidden;
        --avatar-scale: 1.92;
        --avatar-offset-y: -10%;
        --avatar-hair-scale: 1.92;
        --avatar-hair-offset-y: -10%;
    }

    .mission-hero-portrait .ob-avatar-choice-preview__layer {
        transform: none;
        transform-origin: center top;
    }

    .mission-hero-portrait .ob-avatar-asset,
    .mission-hero-portrait .ob-avatar-svg {
        transform-origin: center top;
    }

    .mission-scene-portrait-frame {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4.9rem;
        aspect-ratio: 1 / 1.05;
        overflow: hidden;
        border-radius: 0.95rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.9) 0%, rgba(9, 14, 28, 0.98) 100%);
    }

    .mission-scene-portrait-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
    }

    .mission-scene-portrait-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        color: #e0f2fe;
        background:
            radial-gradient(circle at top, rgba(125, 211, 252, 0.22), transparent 58%),
            linear-gradient(180deg, rgba(14, 116, 144, 0.32) 0%, rgba(12, 18, 32, 0.92) 100%);
    }

    .mission-scene-portrait-fallback--narrator {
        color: #fde68a;
        background:
            radial-gradient(circle at top, rgba(253, 224, 71, 0.18), transparent 58%),
            linear-gradient(180deg, rgba(120, 53, 15, 0.26) 0%, rgba(12, 18, 32, 0.92) 100%);
    }

    @media (min-width: 640px) {
        .mission-hero-portrait {
            width: 5.6rem;
        }

        .mission-scene-portrait-frame {
            width: 5.6rem;
        }
    }
</style>

<div
    x-data="{
        lines: JSON.parse($el.dataset.sceneLines || '[]'),
        index: 0,
        sceneFinished: $el.dataset.sceneStartsFinished === 'true',
        sceneType: $el.dataset.sceneType || '',
        sceneId: Number($el.dataset.sceneId || 0),
        shouldPersistScene: $el.dataset.sceneCompleted !== 'true',
        sceneFinishUrl: $el.dataset.sceneFinishUrl || '',
        get currentLine() {
            return this.lines[this.index] ?? { speaker: 'narrator', name: 'Narration', text: '' };
        },
        get isLastLine() {
            return this.index >= this.lines.length - 1;
        },
        speakerLabel(line = this.currentLine) {
            if (line.speaker === 'hero') return 'Héros';
            if (line.speaker === 'npc') return line.name || 'Allié';
            return line.name || 'Narration';
        },
        speakerBadge(line = this.currentLine) {
            if (line.speaker === 'hero') return 'border-amber-200/30 bg-amber-200/18 text-amber-50';
            if (line.speaker === 'npc') return 'border-sky-200/30 bg-sky-300/18 text-sky-50';
            return 'border-white/12 bg-white/8 text-stone-100';
        },
        advance() {
            if (this.isLastLine) {
                this.finishScene();
                return;
            }
            this.index += 1;
        },
        finishScene() {
            if (this.shouldPersistScene && this.sceneFinishUrl) {
                window.location.assign(this.sceneFinishUrl);
                return;
            }

            this.sceneFinished = true;
            this.$nextTick(() => document.getElementById('mission-map')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
    }"
    data-scene-lines="{{ $conversationLines->values()->toJson() }}"
    data-scene-starts-finished="{{ $sceneStartsFinished ? 'true' : 'false' }}"
    data-scene-completed="{{ ($selectedScene['completed'] ?? false) ? 'true' : 'false' }}"
    data-scene-type="{{ $selectedScene['scene_type'] ?? '' }}"
    data-scene-id="{{ $selectedScene['scene_id'] ?? '' }}"
    data-scene-finish-url="{{ $sceneFinishUrl ?? '' }}"
    class="flex h-full w-full flex-1 flex-col gap-4"
>
    @if ($selectedMap && $conversationLines->isNotEmpty())
        <section
            x-cloak
            x-show="!sceneFinished"
            x-transition.opacity.duration.250ms
            class="relative min-h-[76svh] overflow-hidden rounded-[2rem] border border-white/10 bg-slate-950"
        >
            <img src="{{ $heroIllustration }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/10 via-slate-950/26 to-slate-950/72"></div>
            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.03)_0%,rgba(255,255,255,0)_12%,rgba(255,255,255,0)_100%)]"></div>

            <div class="relative flex min-h-[76svh] flex-col justify-between p-3 sm:p-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-white/12 bg-black/28 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-100">
                                {{ $sceneTitle }}
                            </span>
                            @if ($selectedWorld)
                                <span class="rounded-full border border-white/10 bg-black/22 px-3 py-1 text-[11px] font-semibold text-stone-200">
                                    {{ $selectedWorld->name }}
                                </span>
                            @endif
                        </div>

                        <button
                            type="button"
                            @click="finishScene()"
                            class="rounded-full border border-white/12 bg-black/22 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-100"
                        >
                            Passer
                        </button>
                    </div>

                    @if ($sceneCast->isNotEmpty())
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach ($sceneCast as $castMember)
                                <span class="shrink-0 rounded-full border border-white/10 bg-black/24 px-3 py-1 text-xs font-semibold text-stone-100">
                                    {{ $castMember['name'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="rounded-[1.2rem] border border-white/10 bg-black/28 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-200 backdrop-blur">
                        Appuie sur suivant pour continuer la scène
                    </div>

                    <div class="mission-rpg-window rounded-[1.8rem] p-3 sm:p-4">
                        <div class="mb-3 flex items-end justify-between gap-2">
                            <div class="rounded-[1.25rem] border border-white/10 bg-black/22 p-1.5">
                                @if ($playerAvatar)
                                    <template x-if="currentLine.speaker === 'hero'">
                                        <div>
                                            <x-onboarding.avatar-mini-preview
                                                class="mission-hero-portrait"
                                                :body-key="$avatarBodyKey"
                                                :hair-key="$avatarHairKey"
                                                :outfit-preset-key="$avatarPresetKey"
                                                :equipped-items="$playerAvatar->equipped_items ?? []"
                                                :skin="data_get($playerAvatar->colors, 'skin', data_get($avatarBodyConfig, 'skin', config('avatar.defaults.skin')))"
                                                :hair-color="data_get($playerAvatar->colors, 'hair', data_get($avatarHairConfig, 'color', config('avatar.defaults.hair_color')))"
                                                :accent="data_get($playerAvatar->colors, 'accent', '#D97745')"
                                                :hair-scale="data_get($avatarBodyConfig, 'hair_scale', '1.00')"
                                                :hair-offset-y="data_get($avatarBodyConfig, 'hair_offset_y', '0%')"
                                            />
                                        </div>
                                    </template>
                                @endif

                                <template x-if="currentLine.speaker === 'npc'">
                                    <div class="mission-scene-portrait-frame">
                                        <template x-if="currentLine.portrait">
                                            <img :src="currentLine.portrait" :alt="speakerLabel()">
                                        </template>
                                        <template x-if="!currentLine.portrait">
                                            <div class="mission-scene-portrait-fallback" x-text="currentLine.initials || 'PN'"></div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="currentLine.speaker === 'narrator'">
                                    <div class="mission-scene-portrait-frame">
                                        <div class="mission-scene-portrait-fallback mission-scene-portrait-fallback--narrator">...</div>
                                    </div>
                                </template>
                            </div>

                            @if ($selectedNode)
                                <div class="rounded-[1rem] border border-white/10 bg-black/20 px-2.5 py-2 text-right">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Mission</p>
                                    <p class="mt-1 text-xs font-semibold text-stone-100 sm:text-sm">{{ $selectedNode->title }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span
                                class="rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em]"
                                :class="speakerBadge()"
                                x-text="speakerLabel()"
                            ></span>

                            <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-300">
                                <span x-text="index + 1"></span>/<span x-text="lines.length"></span>
                            </span>
                        </div>

                        <p class="mt-3 min-h-[3.25rem] text-[15px] leading-6 text-stone-50 sm:min-h-[3.75rem] sm:text-base sm:leading-7" x-text="currentLine.text"></p>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <template x-for="(line, lineIndex) in lines" :key="lineIndex">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full transition"
                                        :class="lineIndex <= index ? 'bg-amber-200 shadow-[0_0_10px_rgba(253,230,138,0.7)]' : 'bg-white/18'"
                                    ></span>
                                </template>
                            </div>

                            <button
                                type="button"
                                @click="advance()"
                                class="rounded-full bg-amber-200 px-4 py-2.5 text-sm font-bold uppercase tracking-[0.16em] text-stone-950"
                                x-text="isLastLine ? 'Voir la carte' : 'Suivant'"
                            ></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div
        x-cloak
        x-show="sceneFinished"
        x-transition.opacity.duration.250ms
        class="flex flex-col gap-4"
    >
        <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-stone-950">
            <img src="{{ $heroIllustration }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center opacity-45">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/20 via-slate-950/64 to-stone-950/92"></div>

            <div class="relative p-4 sm:p-6">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-100/90">Académie des héros</p>
                <h1 class="mt-3 text-3xl font-semibold leading-tight text-stone-50 sm:text-5xl">Carte des missions</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-stone-300">Choisis une route, touche un nœud, puis avance jusqu'au boss.</p>

                @if ($selectedWorld)
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-xs font-semibold text-amber-100">
                            {{ $selectedWorld->name }}
                        </span>
                        @if ($selectedMap)
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-stone-100">
                                {{ $selectedMap->name }}
                            </span>
                        @endif
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-stone-300">
                            {{ $selectedWorld->difficulty }}
                        </span>
                    </div>

                    @if ($selectedMap)
                        <div class="mt-5 rounded-[1.5rem] border border-white/10 bg-stone-950/55 p-4 backdrop-blur">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Progression</p>
                                    <p class="mt-1 text-sm text-stone-200">{{ $selectedMap->getAttribute('ui_progress_percent') }}% de la route nettoyée</p>
                                </div>
                                <span class="rounded-full bg-emerald-300/12 px-3 py-1 text-xs font-semibold text-emerald-100">
                                    {{ $selectedMap->nodes->count() }} nœuds
                                </span>
                            </div>

                            <x-lessons.progress-bar :value="$selectedMap->getAttribute('ui_progress_percent')" class="mt-3" />
                        </div>
                    @endif
                @endif

                @if ($missionWorlds->isNotEmpty())
                    <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($missionWorlds as $world)
                            <a href="{{ route($missionBoardRouteName, ['world' => $world->id]) }}"
                                class="shrink-0 rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] transition {{ $selectedWorld?->id === $world->id ? 'border-amber-200/30 bg-amber-200 text-stone-950' : 'border-white/10 bg-white/5 text-stone-300 hover:border-white/20 hover:text-stone-100' }}">
                                {{ $world->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($selectedWorld && $selectedWorld->maps->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($selectedWorld->maps as $map)
                            <a href="{{ route($missionBoardRouteName, ['world' => $selectedWorld->id, 'map' => $map->id]) }}"
                                class="shrink-0 rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] transition {{ $selectedMap?->id === $map->id ? 'border-emerald-200/30 bg-emerald-200 text-stone-950' : 'border-white/10 bg-white/5 text-stone-300 hover:border-white/20 hover:text-stone-100' }}">
                                {{ $map->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-[1.35rem] border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first('combat') ?: $errors->first() }}
            </div>
        @endif

        @if ($selectedMap)
        <section id="mission-map" class="relative mx-auto w-full max-w-[38rem] overflow-hidden rounded-[2rem] border border-white/10 bg-[#102013] xl:max-w-[42rem]">
            <div class="absolute inset-0 opacity-95" style="background-image:
                linear-gradient(180deg, rgba(12, 23, 19, 0.10) 0%, rgba(12, 23, 19, 0.26) 100%),
                url('{{ $mapIllustration }}');
                background-size: cover;
                background-position: center top;">
            </div>
            <div class="absolute inset-0 bg-black/6"></div>

            <div class="relative border-b border-white/10 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/75">{{ $selectedWorld?->name }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white sm:text-3xl">{{ $selectedMap->name }}</h2>
                        @if ($selectedMap->description)
                            <p class="mt-2 max-w-lg text-sm leading-6 text-white/80">{{ $selectedMap->description }}</p>
                        @endif
                    </div>
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                        Route du boss
                    </span>
                </div>
            </div>

            @if ($nodes->isEmpty())
                <div class="relative p-4 sm:p-5">
                    <div class="mission-dialogue-shell rounded-[1.7rem] p-5 text-sm text-stone-200">
                        Cette carte n'a pas encore de nœuds.
                    </div>
                </div>
            @else
                <div class="relative p-3 sm:p-4">
                    <div class="mx-auto w-full max-w-[30rem]">
                        <div class="mission-route-scroll overflow-y-auto rounded-[1.8rem] border border-white/10 bg-black/8 p-2.5 sm:p-3" style="height: min(78svh, 52rem);">
                            <div class="relative mx-auto w-full max-w-[26rem]" style="height: {{ $mapHeight }}px;">
                            <div class="absolute inset-0 rounded-[1.8rem] border border-white/10" style="background-image:
                                linear-gradient(180deg, rgba(0, 0, 0, 0.04) 0%, rgba(0, 0, 0, 0.18) 100%),
                                url('{{ $mapIllustration }}');
                                background-size: cover;
                                background-position: center top;">
                            </div>

                            @if ($pathD !== '')
                                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="{{ $pathD }}" fill="none" stroke="rgba(92, 58, 20, 0.78)" stroke-linecap="round" stroke-linejoin="round" stroke-width="15"></path>
                                    <path d="{{ $pathD }}" fill="none" stroke="#f5d98d" stroke-linecap="round" stroke-linejoin="round" stroke-width="10.5"></path>
                                    <path d="{{ $pathD }}" fill="none" stroke="rgba(255, 247, 214, 0.78)" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.6"></path>
                                </svg>
                            @endif

                                @foreach ($nodeMarkers as $marker)
                                    @php
                                        $node = $marker['node'];
                                        $isSelected = $selectedNode?->id === $node->id;
                                    @endphp
                                    <a href="{{ route($missionBoardRouteName, ['world' => $selectedWorld?->id, 'map' => $selectedMap->id, 'node' => $node->id]) }}"
                                        class="absolute flex -translate-x-1/2 -translate-y-1/2 flex-col items-center focus:outline-none"
                                        style="left: {{ $marker['x'] }}%; top: {{ $marker['y'] }}%;">
                                        @if ($isSelected)
                                            <span class="mission-node-bubble mb-3 rounded-full border border-white/10 bg-stone-950/90 px-3 py-1 text-[11px] font-semibold text-stone-100">
                                                {{ $node->title }}
                                            </span>
                                        @endif

                                        <span class="relative flex items-center justify-center border-[5px] text-center font-black transition"
                                            style="width: {{ $marker['size'] }}px; height: {{ $marker['size'] }}px; border-radius: {{ $marker['radius'] }}; border-color: {{ $marker['ring'] }}; background: {{ $marker['surface'] }}; color: {{ $marker['ink'] }}; box-shadow: {{ $marker['shadow'] }}{{ $isSelected ? ', 0 0 0 8px rgba(255, 255, 255, 0.14)' : '' }};">
                                            <span class="absolute inset-x-[16%] top-[8%] h-[20%] rounded-full bg-white/30"></span>
                                            <span class="relative">
                                                @switch($node->node_type)
                                                    @case('boss')
                                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7.5 4.5 4.5L12 6l4.5 6L21 7.5 19.5 18h-15L3 7.5Z" />
                                                        </svg>
                                                        @break
                                                    @case('event')
                                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 10.5h9m-9 3h5.25M6 19.5l-1.5 1.5V6A1.5 1.5 0 0 1 6 4.5h12A1.5 1.5 0 0 1 19.5 6v9A1.5 1.5 0 0 1 18 16.5H7.5L6 18Z" />
                                                        </svg>
                                                        @break
                                                    @case('treasure')
                                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5v8.25a1.5 1.5 0 0 1-1.5 1.5h-13.5a1.5 1.5 0 0 1-1.5-1.5V9.75Zm0 0 2.1-4.2A1.5 1.5 0 0 1 7.192 4.5h9.616a1.5 1.5 0 0 1 1.342.84l2.1 4.41M12 9.75v9.75" />
                                                        </svg>
                                                        @break
                                                    @case('checkpoint')
                                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 21V5.25m0 0c2.625 0 3.75-2.25 6.375-2.25S15.375 5.25 18 5.25v8.25c-2.625 0-3.75-2.25-6.375-2.25S7.875 13.5 5.25 13.5" />
                                                        </svg>
                                                        @break
                                                    @case('shop')
                                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V6.75A5.25 5.25 0 0 1 12 1.5a5.25 5.25 0 0 1 5.25 5.25v.75m-12 0h13.5l-.9 12.15a1.5 1.5 0 0 1-1.496 1.35H7.646a1.5 1.5 0 0 1-1.496-1.35L5.25 7.5Z" />
                                                        </svg>
                                                        @break
                                                    @case('rest')
                                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3c-.07.33-.11.67-.11 1.02a8.25 8.25 0 0 0 8.25 8.25c.35 0 .69-.04 1.02-.11Z" />
                                                        </svg>
                                                        @break
                                                    @default
                                                        <span class="{{ $node->node_type === 'combat_elite' ? 'text-3xl' : 'text-2xl' }}">{{ $marker['index'] }}</span>
                                                @endswitch
                                            </span>
                                        </span>

                                        <span class="mt-3 flex items-center gap-1">
                                            @for ($starIndex = 0; $starIndex < 3; $starIndex++)
                                                <span class="h-2.5 w-2.5 rounded-full {{ $starIndex < $marker['stars'] ? 'bg-amber-200 shadow-[0_0_10px_rgba(253,230,138,0.7)]' : 'bg-stone-900/35 ring-1 ring-white/15' }}"></span>
                                            @endfor
                                        </span>

                                        @if ($loop->first)
                                            <span class="mt-2 rounded-full bg-stone-950/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-stone-100">
                                                Départ
                                            </span>
                                        @elseif ($node->node_type === 'boss')
                                            <span class="mt-2 rounded-full bg-rose-950/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-rose-100">
                                                Boss
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <div class="mx-auto grid w-full max-w-[38rem] gap-4 xl:max-w-[42rem]">
            <section class="mission-dialogue-shell rounded-[1.8rem] p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Nœud sélectionné</p>
                        <h3 class="mt-2 text-2xl font-semibold text-stone-50">{{ $selectedNode?->title ?? 'Choisis un nœud' }}</h3>
                        @if ($selectedNode?->subtitle)
                            <p class="mt-1 text-sm text-stone-300">{{ $selectedNode->subtitle }}</p>
                        @endif
                    </div>
                    @if ($selectedNode)
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-stone-100">
                            {{ $selectedStatusLabel }}
                        </span>
                    @endif
                </div>

                @if ($selectedNode)
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-xs font-semibold text-amber-100">
                            {{ $selectedNode->getAttribute('ui_badge') }}
                        </span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-stone-300">
                            {{ $selectedNode->getAttribute('ui_label') }}
                        </span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-stone-300">
                            {{ $selectedMap->name }}
                        </span>
                    </div>

                    @if ($selectedNode->description)
                        <p class="mt-4 text-sm leading-6 text-stone-300">{{ $selectedNode->description }}</p>
                    @endif
                @else
                    <p class="mt-4 text-sm leading-6 text-stone-300">Choisis un point sur la carte pour voir la scène et lancer la mission.</p>
                @endif

                @if ($selectedNode?->getAttribute('ui_lesson_url'))
                    <a href="{{ $selectedNode->getAttribute('ui_lesson_url') }}"
                        class="mt-4 inline-flex rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                        Ouvrir la leçon liée
                    </a>
                @endif

                @if ($companions->isEmpty())
                    <div class="mt-4 rounded-[1.4rem] border border-amber-200/20 bg-amber-300/10 px-4 py-4 text-sm leading-6 text-amber-50">
                        Aucun compagnon n’est disponible pour partir en mission.
                    </div>
                @elseif (! $combatNodeSelected)
                    <div class="mt-4 rounded-[1.4rem] border border-sky-200/20 bg-sky-300/10 px-4 py-4 text-sm leading-6 text-sky-50">
                        Ce nœud n’ouvre pas encore de combat. Garde-le pour la suite du système.
                    </div>
                @else
                    <div class="mt-5 grid gap-4">
                        <div class="grid gap-3 rounded-[1.45rem] border border-white/10 bg-stone-950/45 p-4">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-[1.15rem] border border-white/10 bg-white/5 px-3 py-3">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Builds</p>
                                    <p class="mt-2 text-sm font-semibold text-stone-50">Classe, rôle, spécialisation</p>
                                    <p class="mt-1 text-xs leading-5 text-stone-400">Le héros et les compagnons préparent maintenant un vrai loadout avant chaque combat.</p>
                                </div>
                                <div class="rounded-[1.15rem] border border-white/10 bg-white/5 px-3 py-3">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Loadout</p>
                                    <p class="mt-2 text-sm font-semibold text-stone-50">4 actifs · 3 passifs · 1 ultime</p>
                                    <p class="mt-1 text-xs leading-5 text-stone-400">Les compétences équipées définissent le style de jeu réel en combat.</p>
                                </div>
                                <div class="rounded-[1.15rem] border border-white/10 bg-white/5 px-3 py-3">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Synergies</p>
                                    <p class="mt-2 text-sm font-semibold text-stone-50">Composition d’équipe</p>
                                    <p class="mt-1 text-xs leading-5 text-stone-400">Tank, DPS distance, support et contrôle créent désormais de vraies synergies.</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-[1.25rem] border border-amber-200/18 bg-amber-200/10 p-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Départ</p>
                                    <p class="mt-1 text-sm text-stone-200">
                                        {{ $missionLocked ? 'Ce nœud est encore verrouillé.' : 'Passe par l’écran de préparation pour configurer l’escouade et le build.' }}
                                    </p>
                                </div>
                                @if ($missionLocked)
                                    <span class="shrink-0 rounded-full bg-stone-700 px-5 py-3 text-sm font-bold uppercase tracking-[0.16em] text-stone-400">
                                        Préparer le combat
                                    </span>
                                @else
                                    <a
                                        href="{{ route('combat.prepare', $selectedNode) }}"
                                        class="shrink-0 rounded-full bg-amber-200 px-5 py-3 text-sm font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100"
                                    >
                                        Préparer le combat
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        </div>
        @else
            <section class="mission-dialogue-shell rounded-[1.8rem] p-5 text-sm text-stone-200">
                Aucune carte de mission n'est disponible pour le moment.
            </section>
        @endif
    </div>
</div>
