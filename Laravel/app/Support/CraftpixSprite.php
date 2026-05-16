<?php

namespace App\Support;

/**
 * Describes how to read Craftpix-style monster sprite sheets.
 *
 * Craftpix asset packs ship one PNG per animation state ("Idle.png",
 * "Walk.png", "Attack.png"…) instead of one giant multi-direction sheet
 * like the LPC universal layout. Each PNG is just a strip of frames –
 * sometimes single-row (Demons: 128×128 frames, 5–12 frames wide), sometimes
 * wrapped to multiple rows for layout efficiency (Skeletons: 64×64 frames
 * laid out 6×4, 8×4, 9×4 etc.). All frames face the same direction.
 *
 * This helper turns a directory path into a fully-described state set the
 * combat renderer can consume. The CombatService just calls
 * `CraftpixSprite::resolve()` and the rendering layer hides the multi-file
 * detail behind a single `craftpix_states` payload.
 */
final class CraftpixSprite
{
    /**
     * Filename + frame timing per kind. The keys ("idle", "walk"…) are the
     * runtime states the JS animator picks (matching the LPC profile naming).
     * Each state lists the candidate filenames in priority order – the first
     * one that exists on disk wins. Missing states are skipped silently.
     */
    private const KIND_PROFILES = [
        'demon' => [
            'frame_size' => 128,
            'states' => [
                'idle'   => ['files' => ['Idle.png'],            'fps' => 8,  'loop' => true],
                'walk'   => ['files' => ['Walk.png'],            'fps' => 10, 'loop' => true],
                'run'    => ['files' => ['Charge.png', 'Walk.png'], 'fps' => 14, 'loop' => true],
                'attack' => ['files' => ['Attack.png'],          'fps' => 14, 'loop' => false],
                'hurt'   => ['files' => ['Hurt.png'],            'fps' => 12, 'loop' => false],
                'death'  => ['files' => ['Dead.png', 'Death.png'], 'fps' => 10, 'loop' => false],
            ],
        ],
        'skeleton' => [
            'frame_size' => 64,
            'name_template' => '{kind}{index}_{State}_without_shadow.png',
            'states' => [
                'idle'   => ['state' => 'Idle',   'fps' => 8,  'loop' => true],
                'walk'   => ['state' => 'Walk',   'fps' => 10, 'loop' => true],
                'run'    => ['state' => 'Run',    'fps' => 14, 'loop' => true],
                'attack' => ['state' => 'Attack', 'fps' => 14, 'loop' => false],
                'hurt'   => ['state' => 'Hurt',   'fps' => 12, 'loop' => false],
                'death'  => ['state' => 'Death',  'fps' => 10, 'loop' => false],
            ],
        ],
    ];

    /**
     * Detect the kind ("demon", "skeleton"…) from a sprite path and return
     * its Craftpix metadata, or null if the path doesn't look Craftpix.
     *
     * Accepted path shapes (case-insensitive, with or without leading slash):
     *   images/monsters/demon_1/Idle.png      → kind=demon,   index=1
     *   images/monsters/skeleton_2/...        → kind=skeleton,index=2
     *   images/monsters/demon_3              → kind=demon,   index=3 (folder)
     */
    public static function resolve(string $publicPath): ?array
    {
        $normalized = ltrim($publicPath, '/');

        if (! preg_match('#(?:^|/)images/monsters/(demon|skeleton)_(\d+)(?:/|$)#i', $normalized, $matches)) {
            return null;
        }

        $kind = strtolower($matches[1]);
        $index = (int) $matches[2];
        $profile = self::KIND_PROFILES[$kind] ?? null;
        if (! $profile) {
            return null;
        }

        $folderRelative = "images/monsters/{$kind}_{$index}";
        $folderAbsolute = public_path($folderRelative);
        if (! is_dir($folderAbsolute)) {
            return null;
        }

        $frameSize = (int) $profile['frame_size'];
        $states = [];

        foreach ($profile['states'] as $stateKey => $stateConfig) {
            $stateMeta = self::resolveState(
                $kind,
                $index,
                $folderAbsolute,
                $folderRelative,
                $frameSize,
                $stateKey,
                $stateConfig,
                $profile['name_template'] ?? null,
            );

            if ($stateMeta !== null) {
                $states[$stateKey] = $stateMeta;
            }
        }

        if ($states === []) {
            return null;
        }

        // Pick a sensible default state to render before the JS animator wakes up.
        $defaultKey = isset($states['idle']) ? 'idle' : array_key_first($states);

        return [
            'kind' => $kind,
            'index' => $index,
            'frame_size' => $frameSize,
            'frame_width' => $frameSize,
            'frame_height' => $frameSize,
            'default_state' => $defaultKey,
            'states' => $states,
        ];
    }

    /**
     * @param  array<string, mixed>  $stateConfig
     */
    private static function resolveState(
        string $kind,
        int $index,
        string $folderAbsolute,
        string $folderRelative,
        int $frameSize,
        string $stateKey,
        array $stateConfig,
        ?string $nameTemplate,
    ): ?array {
        $candidates = [];

        if (isset($stateConfig['files']) && is_array($stateConfig['files'])) {
            $candidates = $stateConfig['files'];
        } elseif (isset($stateConfig['state']) && is_string($nameTemplate)) {
            $candidates = [strtr($nameTemplate, [
                '{kind}'  => ucfirst($kind),
                '{index}' => (string) $index,
                '{State}' => $stateConfig['state'],
                '{state}' => strtolower($stateConfig['state']),
            ])];
        }

        foreach ($candidates as $candidate) {
            $absolute = $folderAbsolute.'/'.$candidate;
            if (! is_file($absolute)) {
                continue;
            }

            $dimensions = @getimagesize($absolute);
            if (! is_array($dimensions) || ($dimensions[0] ?? 0) <= 0 || ($dimensions[1] ?? 0) <= 0) {
                continue;
            }

            $cols = max(1, (int) round($dimensions[0] / $frameSize));
            $rows = max(1, (int) round($dimensions[1] / $frameSize));
            $totalFrames = $cols * $rows;

            return [
                'state' => $stateKey,
                'url' => '/'.$folderRelative.'/'.$candidate,
                'cols' => $cols,
                'rows' => $rows,
                'frames' => $totalFrames,
                'fps' => (int) ($stateConfig['fps'] ?? 8),
                'loop' => (bool) ($stateConfig['loop'] ?? true),
            ];
        }

        return null;
    }
}
