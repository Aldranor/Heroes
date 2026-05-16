<?php

namespace App\Support;

/**
 * Encapsulates the layout of a Liberated Pixel Cup "Universal" sprite sheet.
 *
 * A standard LPC universal sheet is a 13 × 21 grid of 64×64 frames:
 *
 *   Rows  0-3  : spellcast  (north, west, south, east)
 *   Rows  4-7  : thrust     (north, west, south, east)
 *   Rows  8-11 : walk       (north, west, south, east)
 *   Rows 12-15 : slash      (north, west, south, east)
 *   Rows 16-19 : shoot      (north, west, south, east)
 *   Row  20    : hurt
 *
 * Within a single animation row, column 0 is the rest pose and the remaining
 * columns are sequential animation frames.
 *
 * This class is the single source of truth for "what row do I need for an
 * idle west-facing walk pose" so views and services no longer hard-code
 * row indices.
 */
final class LpcSprite
{
    public const POSE_SPELLCAST = 'spellcast';
    public const POSE_THRUST    = 'thrust';
    public const POSE_WALK      = 'walk';
    public const POSE_SLASH     = 'slash';
    public const POSE_SHOOT     = 'shoot';
    public const POSE_HURT      = 'hurt';

    public const DIRECTION_NORTH = 'north';
    public const DIRECTION_WEST  = 'west';
    public const DIRECTION_SOUTH = 'south';
    public const DIRECTION_EAST  = 'east';

    /** First row of each pose on the universal sheet. */
    private const POSE_BASE_ROW = [
        self::POSE_SPELLCAST => 0,
        self::POSE_THRUST    => 4,
        self::POSE_WALK      => 8,
        self::POSE_SLASH     => 12,
        self::POSE_SHOOT     => 16,
    ];

    /** Direction offset within a 4-row pose block. */
    private const DIRECTION_OFFSET = [
        self::DIRECTION_NORTH => 0,
        self::DIRECTION_WEST  => 1,
        self::DIRECTION_SOUTH => 2,
        self::DIRECTION_EAST  => 3,
    ];

    private const HURT_ROW = 20;

    public const UNIVERSAL_COLUMN_COUNT = 13;

    /** Total rows expected on a full universal sheet. */
    public const UNIVERSAL_ROW_COUNT = 21;

    /** Total rows expected on the extended LPC combat sheet. */
    public const EXTENDED_ROW_COUNT = 54;

    /**
     * Resolve the row index for a given pose + direction on a universal sheet.
     */
    public static function row(string $pose, string $direction = self::DIRECTION_SOUTH): int
    {
        if ($pose === self::POSE_HURT) {
            return self::HURT_ROW;
        }

        $base = self::POSE_BASE_ROW[$pose] ?? self::POSE_BASE_ROW[self::POSE_WALK];
        $offset = self::DIRECTION_OFFSET[$direction] ?? self::DIRECTION_OFFSET[self::DIRECTION_SOUTH];

        return $base + $offset;
    }

    /**
     * Column to use for a "neutral" idle pose. Frame 1 of a walk cycle reads
     * as a relaxed standing pose on most LPC bodies.
     */
    public static function neutralColumn(): int
    {
        return 1;
    }

    /**
     * Column to use for a combat-ready idle stance on LPC universal sheets.
     * Those sheets do not have a dedicated "battle idle" strip, so we treat
     * the neutral frame of the thrust pose as the ready stance.
     */
    public static function combatReadyColumn(): int
    {
        return 0;
    }

    /**
     * Column to use when an actor is mid-attack. Picks roughly the apex of
     * the swing in a typical 6–7 frame strip, while staying in bounds.
     */
    public static function attackColumn(int $columnsAvailable): int
    {
        $columnsAvailable = max(1, $columnsAvailable);

        return min($columnsAvailable - 1, max(2, (int) floor($columnsAvailable * 0.33)));
    }

    /**
     * Column to use when an actor is being hit. Frame 0 (rest) reads as a
     * brief stagger when paired with the .combat-battler--hit shake.
     */
    public static function hitColumn(): int
    {
        return 0;
    }

    /**
     * Map a horizontal facing ('left'|'right') to the LPC direction we render.
     *
     * We always render the WEST row and rely on a horizontal CSS flip to
     * achieve right-facing actors — that way 4-direction sheets that omit
     * the EAST row still work correctly.
     */
    public static function directionForFacing(string $facing): string
    {
        return self::DIRECTION_WEST;
    }

    public static function shouldFlipForFacing(string $facing): bool
    {
        return $facing === 'right';
    }

    public static function combatIdleRowForSheet(int $rows, int $simpleFallbackRow = 1): int
    {
        if ($rows <= 1) {
            return 0;
        }

        if ($rows >= self::EXTENDED_ROW_COUNT) {
            return 42 + self::DIRECTION_OFFSET[self::DIRECTION_WEST];
        }

        $universalRow = self::row(self::POSE_THRUST, self::DIRECTION_WEST);

        if ($rows >= self::UNIVERSAL_ROW_COUNT) {
            return $universalRow;
        }

        return min($simpleFallbackRow, max(0, $rows - 1));
    }

    public static function idleColumnForSheet(int $rows): int
    {
        return $rows >= self::UNIVERSAL_ROW_COUNT
            ? self::combatReadyColumn()
            : self::neutralColumn();
    }

    /**
     * Pick the right row for an arbitrary sprite sheet whose row count we
     * know. For full universal sheets (21+ rows) we use the LPC walk-west
     * row; for simpler sheets (e.g. a 4-row idle/walk strip) we fall back
     * to the first usable row.
     */
    public static function leftFacingRowForSheet(int $rows, int $simpleFallbackRow = 1): int
    {
        return self::combatIdleRowForSheet($rows, $simpleFallbackRow);
    }
}
