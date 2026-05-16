<?php

namespace App\Support;

use App\Enums\BattleActorType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CombatViewFactory
{
    public function __construct(
        private readonly AvatarAssetCatalog $avatarAssets,
        private readonly AvatarSvgRenderer $avatarRenderer,
    ) {}

    public function decorate(array $viewData): array
    {
        $playerActors = $this->toCollection($viewData['playerActors'] ?? []);
        $enemyActors = $this->toCollection($viewData['enemyActors'] ?? []);
        $currentActor = is_array($viewData['currentActor'] ?? null) ? $viewData['currentActor'] : null;
        $lastAction = is_array($viewData['lastAction'] ?? null) ? $viewData['lastAction'] : [];
        $availableSkills = $this->toCollection($viewData['availableSkills'] ?? []);

        $viewData['musicUrl'] = $this->musicUrl($viewData['context'] ?? []);
        $viewData['playerBattlers'] = $this->buildBattlers($playerActors, 'hero', $currentActor, $lastAction);
        $viewData['enemyBattlers'] = $this->buildBattlers($enemyActors, 'enemy', $currentActor, $lastAction);
        $viewData['latestLog'] = collect($viewData['battleSummary']['logs'] ?? [])->last();
        $viewData['skillButtons'] = $this->buildSkillButtons($availableSkills, $currentActor);
        $viewData['commandMenus'] = $this->buildCommandMenus(collect($viewData['skillButtons']), $currentActor);
        $viewData['partyRows'] = $this->buildPartyRows($playerActors, $currentActor);
        $viewData['battleOutcome'] = $this->buildBattleOutcome($viewData['battle'] ?? null);
        $viewData['currentActorStats'] = $this->buildCurrentActorStats($currentActor, $viewData['academic'] ?? []);
        $viewData['sidebarLogs'] = $this->buildSidebarLogs($viewData['battleSummary']['logs'] ?? []);

        return $viewData;
    }

    private function buildBattlers(Collection $actors, string $side, ?array $currentActor, array $lastAction): array
    {
        return $actors
            ->map(fn (array $actor): array => $this->buildBattler($actor, $side, $currentActor, $lastAction))
            ->values()
            ->all();
    }

    private function buildBattler(array $actor, string $side, ?array $currentActor, array $lastAction): array
    {
        $isCurrent = ($currentActor['key'] ?? null) === ($actor['key'] ?? null);
        $isAttacker = ($lastAction['actor_key'] ?? null) === ($actor['key'] ?? null);
        $isTarget = ($lastAction['target_key'] ?? null) === ($actor['key'] ?? null);
        $isDefeated = ($actor['current_hp'] ?? 0) <= 0;

        // Note: we deliberately do NOT add `combat-battler--attacking` or
        // `combat-battler--hit` to the rendered HTML. They used to be the
        // sole visual feedback for an action, but now the JS replay loop
        // adds those classes only during the strike animation and removes
        // them right after. Persisting them in the rendered output would
        // freeze the attacker on the final slash frame (combat_attack is a
        // non-looping animation) and the target on the hurt pose.
        return [
            'key' => $actor['key'] ?? null,
            'name' => $actor['name'] ?? '',
            'is_current' => $isCurrent,
            'classes' => $this->classNames([
                'combat-battler',
                $side === 'enemy' ? 'combat-battler--enemy' : 'combat-battler--ally',
                $isCurrent ? 'combat-battler--current' : null,
                $isDefeated ? 'combat-battler--defeated' : null,
                ! $isCurrent ? 'combat-battler--dimmed' : null,
            ]),
            'style' => sprintf(
                'left: %s%%; top: %s%%; z-index: %s;',
                $actor['position_x'] ?? 0,
                $actor['position_y'] ?? 0,
                $actor['layer_order'] ?? 1,
            ),
            'sprite' => $this->buildBattlerSprite($actor, $side, $isAttacker, $isTarget, $isDefeated),
        ];
    }

    private function buildBattlerSprite(array $actor, string $side, bool $isAttacker, bool $isTarget, bool $isDefeated): array
    {
        $spriteMeta = is_array($actor['sprite'] ?? null) ? $actor['sprite'] : null;
        $facing = (string) ($actor['direction'] ?? ($side === 'hero' ? 'right' : 'left'));
        $animationState = (string) ($actor['animation_state'] ?? 'idle');
        $animationProfile = $spriteMeta['animation_profile'] ?? null;
        $animationMap = SpriteAnimationMap::resolveSet(
            $animationProfile,
            (int) ($spriteMeta['rows'] ?? 0),
            (int) ($spriteMeta['columns'] ?? 0),
            $facing,
        );

        // The initial sprite frame is rendered server-side and shown until
        // the JS animator's first tick (~130 ms). We deliberately render
        // attackers and targets in their idle pose rather than the slash /
        // hurt frame, so a stale page reload (or the post-replay re-render)
        // doesn't flash a frozen mid-attack frame. The active strike feedback
        // is owned by the JS replay loop, which adds the right classes only
        // for the duration of each blow.
        $initialAnimationKey = $isDefeated ? 'dead' : 'combat_idle';

        $initialAnimation = $animationMap[$initialAnimationKey]
            ?? $animationMap['combat_idle']
            ?? $animationMap['idle']
            ?? null;

        $defaultIdleRow = LpcSprite::combatIdleRowForSheet(LpcSprite::UNIVERSAL_ROW_COUNT);
        $defaultIdleCol = LpcSprite::combatReadyColumn();
        $initialSequenceOffset = (int) ($initialAnimation['sequence'][0] ?? 0);
        // Always pin the initial column to the idle pose – action frames are
        // owned by the JS replay loop. Pass `false, false` so a stale render
        // never freezes the sprite on the attack / hit column.
        $spriteCol = $initialAnimation
            ? min(
                max(0, (int) ($spriteMeta['columns'] ?? 1) - 1),
                (int) ($initialAnimation['start_column'] ?? 0) + $initialSequenceOffset,
            )
            : $this->resolveStaticSpriteFrame($actor, false, false, $defaultIdleCol);
        $spriteRow = (int) ($initialAnimation['row'] ?? ($spriteMeta['left_view_row'] ?? $defaultIdleRow));

        if (($actor['actor_type'] ?? null) === BattleActorType::Hero->value) {
            $avatarResult = $this->buildAvatarSpriteSheetPayload(
                is_array($actor['avatar'] ?? null) ? $actor['avatar'] : [],
                $spriteRow,
                $spriteCol,
                $animationState,
                $animationMap,
                $facing,
            );

            if ($avatarResult !== null) {
                return [
                    'type' => 'sheet',
                    // The "safe attack flip" class kicks the sprite to a CSS
                    // mirror during the slash frame when the hero falls back
                    // to the universal LPC layout (where the right-facing
                    // attack row aliases the left-facing one and so needs a
                    // horizontal flip to point at the enemy correctly).
                    'class' => $this->classNames([
                        'combat-sprite-sheet--party',
                        ($avatarResult['needs_attack_flip'] ?? false) ? 'combat-sprite-sheet--safe-attack-flip' : null,
                    ]),
                    'payload' => $avatarResult['payload'],
                ];
            }
        }

        $spriteClass = $this->classNames([
            'combat-sprite-sheet--party',
            (($spriteMeta['animation_profile'] ?? null) === 'craftpix') ? 'combat-sprite-sheet--craftpix' : null,
        ]);

        if ($spriteMeta !== null) {
            $payload = $this->buildSpriteSheetPayload(
                src: $spriteMeta['url'] ?? null,
                layers: [],
                columns: (int) ($spriteMeta['columns'] ?? 1),
                rows: (int) ($spriteMeta['rows'] ?? 1),
                row: $spriteRow,
                col: $spriteCol,
                baseCol: $spriteCol,
                animationState: $animationState,
                styleString: '',
                animationMap: $animationMap,
            );

            // Forward the Craftpix multi-state descriptor so the blade can
            // render every state's PNG and the JS animator can swap which
            // one is visible based on the actor's current state.
            if (($spriteMeta['animation_profile'] ?? null) === 'craftpix' && isset($spriteMeta['craftpix'])) {
                $payload['craftpix'] = $spriteMeta['craftpix'];
            }

            return [
                'type' => 'sheet',
                'class' => $spriteClass,
                'payload' => $payload,
            ];
        }

        if (is_string($actor['sprite_sheet'] ?? null) && ($actor['sprite_sheet'] ?? '') !== '') {
            return [
                'type' => 'image',
                'src' => $actor['sprite_sheet'],
                'alt' => $actor['name'] ?? '',
                'style' => '',
            ];
        }

        return [
            'type' => 'fallback',
            'class' => $side === 'hero' ? 'combat-fallback' : 'combat-fallback combat-fallback--enemy',
            'label' => Str::upper(Str::substr((string) ($actor['name'] ?? ''), 0, 2)),
        ];
    }

    private function buildAvatarSpriteSheetPayload(
        array $avatar,
        int $row,
        int $col,
        string $animationState,
        array $animationMap,
        string $facing = 'right',
    ): ?array {
        $bodyKey = $avatar['body_key'] ?? null;
        $hairKey = $avatar['hair_key'] ?? null;
        $outfitPresetKey = $avatar['outfit_preset_key'] ?? null;
        $equippedItems = is_array($avatar['equipped_items'] ?? null) ? $avatar['equipped_items'] : [];

        $bodyConfig = is_string($bodyKey) ? ($this->avatarAssets->bodyAsset($bodyKey) ?? []) : [];
        $hairConfig = is_string($hairKey) ? ($this->avatarAssets->hairAsset($hairKey) ?? []) : [];

        $resolvedEquipment = array_merge($equippedItems, ['body' => $bodyKey]);
        $resolvedSkin = $avatar['skin'] ?? data_get($bodyConfig, 'skin', config('avatar.defaults.skin'));
        $resolvedHairColor = $avatar['hair_color'] ?? data_get($hairConfig, 'color', config('avatar.defaults.hair_color'));

        $styleString = collect(array_merge(
            $this->avatarRenderer->skinPalette((string) $resolvedSkin),
            $this->avatarRenderer->hairPalette((string) $resolvedHairColor),
            $this->avatarRenderer->outfitPalette((string) ($avatar['accent'] ?? '#4C6FFF')),
        ))->map(fn (string $value, string $key): string => "{$key}: {$value}")
            ->implode('; ');

        $sheetLayers = [];
        $sheetColumns = 1;

        $this->pushAvatarSheetLayer($sheetLayers, $sheetColumns, is_string($bodyKey) ? $this->avatarAssets->bodyAsset($bodyKey) : null, $row);

        $resolvedOutfit = $this->avatarAssets->resolveOutfitPreset(
            is_string($outfitPresetKey) ? $outfitPresetKey : null,
            $resolvedEquipment,
        );
        $resolvedSlots = $resolvedOutfit['slots'] ?? [];

        foreach (['full_outfit', 'back', 'pants', 'shoes', 'top'] as $slot) {
            $assetKey = $resolvedSlots[$slot] ?? null;
            if (is_string($assetKey) && $assetKey !== '') {
                $this->pushAvatarSheetLayer($sheetLayers, $sheetColumns, $this->avatarAssets->wearableAsset($assetKey), $row);
            }
        }

        if (is_string($hairKey) && $hairKey !== '') {
            $this->pushAvatarSheetLayer($sheetLayers, $sheetColumns, $this->avatarAssets->hairAsset($hairKey), $row);
        }

        foreach (['facial_hair', 'helmet', 'accessory'] as $slot) {
            $assetKey = $resolvedSlots[$slot] ?? null;
            if (is_string($assetKey) && $assetKey !== '') {
                $this->pushAvatarSheetLayer($sheetLayers, $sheetColumns, $this->avatarAssets->wearableAsset($assetKey), $row);
            }
        }

        if ($sheetLayers === []) {
            return null;
        }

        $sheetRows = max(1, (int) (collect($sheetLayers)->max('rows') ?? LpcSprite::UNIVERSAL_ROW_COUNT));

        // If any layer is shorter than the extended LPC sheet, the body might
        // animate at extended-only rows (combat_attack at 53, run at 41…)
        // while equipment sheets can't render those frames – they'd translate
        // off-screen, making the clothes "disappear" mid-strike. To keep the
        // whole hero consistent we re-resolve the animation map against the
        // universal row layout (≤ 20). Every LPC layer supports those rows
        // so body + equipment stay in sync.
        $minLayerRows = (int) (collect($sheetLayers)->min('rows') ?? $sheetRows);
        $safeAnimationMap = $animationMap;
        $safeRow = $row;
        $needsAttackFlip = false;
        if ($minLayerRows < LpcSprite::EXTENDED_ROW_COUNT) {
            $safeAnimationMap = SpriteAnimationMap::resolveSet(
                'lpc',
                LpcSprite::UNIVERSAL_ROW_COUNT,
                LpcSprite::UNIVERSAL_COLUMN_COUNT,
                $facing,
            );

            // Clamp the initial row too: if the parent computed a row past
            // the universal layout (e.g. row 53 for the extended slash) the
            // first frame would render the body correctly but punt every
            // equipment layer off-screen until the JS animator's first tick.
            // Snap it down to the universal equivalent right away.
            if ($row >= LpcSprite::UNIVERSAL_ROW_COUNT) {
                $safeRow = (int) ($safeAnimationMap['combat_idle']['row']
                    ?? $safeAnimationMap['idle']['row']
                    ?? LpcSprite::combatIdleRowForSheet(LpcSprite::UNIVERSAL_ROW_COUNT));
            }

            // In the universal LPC profile the `right` direction of
            // combat_attack aliases the `left` row (the original artwork
            // omits a dedicated east-facing slash and relies on a CSS
            // mirror). So a hero facing right would visibly slash to the
            // left in fallback mode — apply a horizontal flip during the
            // attack frame to point them at the enemy again.
            $needsAttackFlip = $facing === 'right';
        }

        return [
            'payload' => $this->buildSpriteSheetPayload(
                src: null,
                layers: $sheetLayers,
                columns: $sheetColumns,
                rows: $sheetRows,
                row: $safeRow,
                col: $col,
                baseCol: $col,
                animationState: $animationState,
                styleString: $styleString,
                animationMap: $safeAnimationMap,
            ),
            'needs_attack_flip' => $needsAttackFlip,
        ];
    }

    private function pushAvatarSheetLayer(array &$sheetLayers, int &$sheetColumns, ?array $asset, int $row): void
    {
        $metadata = $this->avatarRenderer->combatSheetMetadata($asset, $row);

        if (! is_array($metadata)) {
            return;
        }

        $sheetColumns = max($sheetColumns, (int) ($metadata['columns'] ?? 1));
        $sheetLayers[] = $metadata;
    }

    private function buildSpriteSheetPayload(
        ?string $src,
        array $layers,
        int $columns,
        int $rows,
        int $row,
        int $col,
        int $baseCol,
        string $animationState,
        string $styleString,
        array $animationMap,
    ): array {
        $resolvedLayers = collect($layers)
            ->filter(fn ($layer): bool => is_array($layer) && is_string($layer['src'] ?? null) && ($layer['src'] ?? '') !== '')
            ->map(fn (array $layer): array => [
                'src' => $layer['src'],
                'style' => sprintf(
                    '--combat-sprite-cols: %d; --combat-sprite-rows: %d;',
                    (int) ($layer['columns'] ?? $columns),
                    (int) ($layer['rows'] ?? $rows),
                ),
            ])
            ->values()
            ->all();

        if ($resolvedLayers === [] && is_string($src) && $src !== '') {
            $resolvedLayers = [[
                'src' => $src,
                'style' => sprintf('--combat-sprite-cols: %d; --combat-sprite-rows: %d;', max(1, $columns), max(1, $rows)),
            ]];
        }

        $resolvedColumns = max(1, $columns ?: 1);
        $resolvedRows = max(1, $rows ?: 1);

        return [
            'layers' => $resolvedLayers,
            'columns' => $resolvedColumns,
            'rows' => $resolvedRows,
            'base_col' => max(0, $baseCol),
            'animation_state' => $animationState,
            'animation_map_json' => $animationMap !== [] ? json_encode($animationMap, JSON_THROW_ON_ERROR) : null,
            'style' => $this->inlineStyle([
                "--combat-sprite-cols: {$resolvedColumns}",
                "--combat-sprite-rows: {$resolvedRows}",
                '--combat-sprite-row: '.max(0, $row),
                '--combat-sprite-col: '.max(0, $col),
                $styleString,
            ]),
        ];
    }

    private function buildSkillButtons(Collection $availableSkills, ?array $currentActor): array
    {
        return $availableSkills
            ->map(function (array $skill) use ($currentActor): array {
                $slug = (string) ($skill['slug'] ?? '');
                $cooldown = (int) data_get($currentActor, 'cooldowns.'.$slug, 0);
                $targetLabel = match ($skill['target'] ?? 'enemy') {
                    'ally' => 'Allié',
                    'self' => 'Soi',
                    'all_enemies' => 'Tous',
                    default => 'Ennemi',
                };

                return [
                    'slug' => $slug,
                    'label' => Str::limit((string) ($skill['label'] ?? ''), 22),
                    'type' => (string) ($skill['type'] ?? 'attack'),
                    'mana_cost' => (int) ($skill['mana_cost'] ?? 0),
                    'target_label' => $targetLabel,
                    'target_type' => $skill['target'] ?? 'enemy',
                    'cooldown' => $cooldown,
                ];
            })
            ->values()
            ->all();
    }

    private function buildCommandMenus(Collection $skills, ?array $currentActor): array
    {
        $attackSkill = $skills->firstWhere('slug', 'attaque-simple')
            ?? $skills->first(fn (array $skill): bool => ($skill['type'] ?? null) === 'attack' && (int) ($skill['mana_cost'] ?? 0) === 0)
            ?? $skills->first(fn (array $skill): bool => ($skill['type'] ?? null) === 'attack');

        $guardSkill = $skills->firstWhere('slug', 'defense-gardee')
            ?? $skills->first(fn (array $skill): bool => ($skill['type'] ?? null) === 'defense');

        $primarySkillSlugs = collect([
            $attackSkill['slug'] ?? null,
            $guardSkill['slug'] ?? null,
        ])->filter()->values();

        $specialSkills = $skills
            ->reject(fn (array $skill): bool => $primarySkillSlugs->contains($skill['slug'] ?? null))
            ->values();

        $root = collect();

        if (is_array($attackSkill)) {
            $root->push([
                'kind' => 'skill',
                'label' => 'Attaque',
                'meta' => 'Action directe',
                'skill' => $attackSkill,
            ]);
        }

        if ($specialSkills->isNotEmpty()) {
            $root->push([
                'kind' => 'submenu',
                'label' => 'Compétences',
                'meta' => sprintf('%d choix', $specialSkills->count()),
                'menu' => 'skills',
            ]);
        }

        if (is_array($guardSkill)) {
            $root->push([
                'kind' => 'skill',
                'label' => 'Garde',
                'meta' => 'Réduit les dégâts',
                'skill' => $guardSkill,
            ]);
        }

        if ($root->isEmpty() && $skills->isNotEmpty()) {
            $fallback = $skills->first();
            $root->push([
                'kind' => 'skill',
                'label' => $fallback['label'] ?? 'Action',
                'meta' => 'Action disponible',
                'skill' => $fallback,
            ]);
        }

        return [
            'root' => $root->values()->all(),
            'skills' => $specialSkills->values()->all(),
            'current_actor_name' => $currentActor['name'] ?? 'Équipe',
        ];
    }

    private function buildPartyRows(Collection $playerActors, ?array $currentActor): array
    {
        return $playerActors
            ->map(function (array $actor) use ($currentActor): array {
                $hpPercent = max(0, min(100, (int) round((($actor['current_hp'] ?? 0) / max(1, $actor['max_hp'] ?? 1)) * 100)));
                $manaPercent = max(0, min(100, (int) round((($actor['current_mana'] ?? 0) / max(1, $actor['max_mana'] ?? 1)) * 100)));
                $isCurrent = ($currentActor['key'] ?? null) === ($actor['key'] ?? null);
                $isDefeated = ($actor['current_hp'] ?? 0) <= 0;

                return [
                    'name' => $actor['name'] ?? '',
                    'level' => (int) ($actor['level'] ?? 1),
                    'current_hp' => (int) ($actor['current_hp'] ?? 0),
                    'max_hp' => (int) ($actor['max_hp'] ?? 0),
                    'current_mana' => (int) ($actor['current_mana'] ?? 0),
                    'max_mana' => (int) ($actor['max_mana'] ?? 0),
                    'hp_percent' => $hpPercent,
                    'mana_percent' => $manaPercent,
                    'state_label' => $isDefeated ? 'KO' : ($isCurrent ? 'Tour' : 'Prêt'),
                    'classes' => $this->classNames([
                        'combat-party-row',
                        $isCurrent ? 'combat-party-row--current' : null,
                        $isDefeated ? 'combat-party-row--defeated' : null,
                    ]),
                    'portrait' => $this->buildPartyPortrait($actor),
                ];
            })
            ->values()
            ->all();
    }

    private function buildPartyPortrait(array $actor): array
    {
        $spriteMeta = is_array($actor['sprite'] ?? null) ? $actor['sprite'] : null;
        $facing = (string) ($actor['direction'] ?? 'right');
        $animationProfile = $spriteMeta['animation_profile'] ?? null;
        $animationMap = SpriteAnimationMap::resolveSet(
            $animationProfile,
            (int) ($spriteMeta['rows'] ?? 0),
            (int) ($spriteMeta['columns'] ?? 0),
            $facing,
        );

        $idleAnimation = $animationMap['combat_idle']
            ?? $animationMap['idle']
            ?? null;

        $columns = max(1, (int) ($spriteMeta['columns'] ?? 1));
        $row = (int) ($idleAnimation['row'] ?? ($spriteMeta['left_view_row'] ?? LpcSprite::combatIdleRowForSheet(LpcSprite::UNIVERSAL_ROW_COUNT)));
        $col = $idleAnimation
            ? min(
                $columns - 1,
                max(0, (int) ($idleAnimation['start_column'] ?? 0) + (int) ($idleAnimation['sequence'][0] ?? 0)),
            )
            : min($columns - 1, max(0, (int) ($spriteMeta['idle_col'] ?? LpcSprite::combatReadyColumn())));

        if (($actor['actor_type'] ?? null) === BattleActorType::Hero->value) {
            $avatarResult = $this->buildAvatarSpriteSheetPayload(
                is_array($actor['avatar'] ?? null) ? $actor['avatar'] : [],
                $row,
                $col,
                'combat_idle',
                $animationMap,
                $facing,
            );

            if ($avatarResult !== null) {
                return [
                    'type' => 'sheet',
                    'class' => 'combat-sprite-sheet--party combat-party-portrait__sprite',
                    'payload' => $this->withoutAnimationMap($avatarResult['payload']),
                ];
            }
        }

        if ($spriteMeta !== null && is_string($spriteMeta['url'] ?? null) && ($spriteMeta['url'] ?? '') !== '') {
            return [
                'type' => 'sheet',
                'class' => 'combat-sprite-sheet--party combat-party-portrait__sprite',
                'payload' => $this->buildSpriteSheetPayload(
                    src: $spriteMeta['url'],
                    layers: [],
                    columns: $columns,
                    rows: max(1, (int) ($spriteMeta['rows'] ?? 1)),
                    row: $row,
                    col: $col,
                    baseCol: $col,
                    animationState: 'combat_idle',
                    styleString: '',
                    animationMap: [],
                ),
            ];
        }

        if (is_string($actor['sprite_sheet'] ?? null) && ($actor['sprite_sheet'] ?? '') !== '') {
            return [
                'type' => 'image',
                'src' => $actor['sprite_sheet'],
                'alt' => $actor['name'] ?? '',
                'style' => '',
            ];
        }

        return [
            'type' => 'fallback',
            'class' => 'combat-fallback combat-party-portrait__fallback',
            'label' => Str::upper(Str::substr((string) ($actor['name'] ?? ''), 0, 2)),
        ];
    }

    private function buildBattleOutcome(mixed $battle): array
    {
        $status = $battle?->status?->value;

        return [
            'title' => $status === 'won' ? 'Victoire' : 'Défaite',
            'message' => $status === 'won'
                ? 'Le nœud est nettoyé et la route suivante s’ouvre.'
                : 'Reviens avec une meilleure escouade ou une meilleure maîtrise.',
        ];
    }

    private function buildCurrentActorStats(?array $currentActor, array $academic): array
    {
        return [
            'hp' => sprintf('%d/%d', (int) ($currentActor['current_hp'] ?? 0), (int) ($currentActor['max_hp'] ?? 0)),
            'mp' => sprintf('%d/%d', (int) ($currentActor['current_mana'] ?? 0), (int) ($currentActor['max_mana'] ?? 0)),
            'crit' => sprintf('%d%%', (int) ($currentActor['crit_rate'] ?? 0)),
            'precision' => (string) (int) ($currentActor['precision'] ?? 0),
            'mastery' => sprintf('%d%%', (int) ($academic['mastery_score'] ?? 0)),
            'damage_bonus' => '+'.(int) ($academic['damage_bonus'] ?? 0),
        ];
    }

    private function buildSidebarLogs(iterable $logs): array
    {
        return collect($logs)
            ->map(fn (array $entry): array => [
                'text' => $entry['text'] ?? '',
                'tone_class' => match ($entry['tone'] ?? 'stone') {
                    'rose' => 'combat-log-entry--rose',
                    'emerald' => 'combat-log-entry--emerald',
                    'amber' => 'combat-log-entry--amber',
                    default => '',
                },
            ])
            ->values()
            ->all();
    }

    private function musicUrl(array $context): ?string
    {
        $nodeType = (string) ($context['node_type'] ?? 'combat_normal');
        $musicFolder = $nodeType === 'boss' ? 'boss' : 'fight';
        $musicDir = public_path("images/music/{$musicFolder}");
        $musicFiles = glob("{$musicDir}/*.{wav,mp3,ogg,webm}", GLOB_BRACE) ?: [];

        if ($musicFiles === []) {
            return null;
        }

        $musicTrack = basename($musicFiles[array_rand($musicFiles)]);

        return asset("images/music/{$musicFolder}/".rawurlencode($musicTrack));
    }

    private function resolveStaticSpriteFrame(array $actor, bool $isAttacker, bool $isTarget, int $defaultIdleCol): int
    {
        $spriteMeta = is_array($actor['sprite'] ?? null) ? $actor['sprite'] : null;
        $columnsAvailable = (int) ($spriteMeta['columns'] ?? 1);
        $idleCol = (int) ($spriteMeta['idle_col'] ?? $defaultIdleCol);
        $col = match (true) {
            $isAttacker => LpcSprite::attackColumn($columnsAvailable),
            $isTarget => LpcSprite::hitColumn(),
            default => $idleCol,
        };

        return min($col, max(0, $columnsAvailable - 1));
    }

    private function classNames(array $classes): string
    {
        return trim(implode(' ', array_values(array_filter($classes, fn ($value): bool => is_string($value) && $value !== ''))));
    }

    private function inlineStyle(array $rules): string
    {
        return implode('; ', array_values(array_filter(array_map(
            fn ($rule): string => trim((string) $rule),
            $rules,
        ))));
    }

    private function withoutAnimationMap(array $payload): array
    {
        $payload['animation_map_json'] = null;

        return $payload;
    }

    private function toCollection(mixed $value): Collection
    {
        return $value instanceof Collection ? $value : collect($value);
    }
}
