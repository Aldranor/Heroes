<?php

return [
    'party' => [
        'max_companions' => 2,
    ],
    'backgrounds' => [
        // Temporary test backgrounds. Later these should be selected from the
        // map / biome type instead of only the combat node type.
        'combat_normal' => 'images/background-combat/battleback9.png',
        'combat_elite' => 'images/background-combat/battleback9.png',
        'boss' => 'images/background-combat/battleback9.png',
    ],
    'defaults' => [
        // Temporary visual fallbacks for combat rendering until companions and
        // enemies are linked to per-map / per-entity battle visuals.
        'companion_sprites' => [
            'images/companions/character-spritesheet-21.png',
            'images/companions/SaraFullSheet.png',
        ],
        'enemy_sprite' => 'images/monsters/imp/walk-vanilla.png',
    ],
    'sprites' => [
        // Used by CombatService when an actor sprite sheet is too small to
        // be a full LPC universal sheet (e.g. a 4-row idle/walk strip).
        // LPC layout itself lives in App\Support\LpcSprite.
        'simple_left_direction_row' => 1,
        'neutral_frame_col' => 0,
    ],
    'skills' => [
        'catalog' => [
            'attaque-simple' => [
                'label' => 'Frappe',
                'type' => 'attack',
                'power' => 15,
                'mana_cost' => 0,
                'cooldown' => 0,
                'target' => 'enemy',
                'description' => 'Une attaque directe et fiable.',
                'animation' => 'attack',
            ],
            'attaque-rapide' => [
                'label' => 'Percée rapide',
                'type' => 'attack',
                'power' => 12,
                'mana_cost' => 0,
                'cooldown' => 1,
                'target' => 'enemy',
                'precision_bonus' => 10,
                'description' => 'Une frappe légère qui touche juste.',
                'animation' => 'attack',
            ],
            'defense-gardee' => [
                'label' => 'Garde',
                'type' => 'defense',
                'mana_cost' => 6,
                'cooldown' => 2,
                'target' => 'self',
                'incoming_damage_multiplier' => 0.62,
                'duration_rounds' => 1,
                'description' => 'Réduit les dégâts encaissés pendant un round.',
                'animation' => 'guard',
            ],
            'focus' => [
                'label' => 'Focus',
                'type' => 'buff',
                'mana_cost' => 7,
                'cooldown' => 2,
                'target' => 'self',
                'attack_bonus' => 6,
                'precision_bonus' => 8,
                'crit_bonus' => 10,
                'duration_rounds' => 2,
                'description' => 'Affûte la prochaine séquence offensive.',
                'animation' => 'cast',
            ],
            'soin-leger' => [
                'label' => 'Second souffle',
                'type' => 'heal',
                'power' => 22,
                'mana_cost' => 10,
                'cooldown' => 3,
                'target' => 'ally',
                'description' => 'Rend un peu de vitalité à un allié.',
                'animation' => 'cast',
            ],
            'anticipation' => [
                'label' => 'Lecture du danger',
                'type' => 'debuff',
                'mana_cost' => 8,
                'cooldown' => 2,
                'target' => 'enemy',
                'attack_bonus' => -5,
                'precision_bonus' => -8,
                'duration_rounds' => 2,
                'description' => 'Fait perdre en assurance la cible.',
                'animation' => 'cast',
            ],
            'heroic-burst' => [
                'label' => 'Impact héroique',
                'type' => 'attack',
                'power' => 28,
                'mana_cost' => 14,
                'cooldown' => 3,
                'target' => 'enemy',
                'crit_bonus' => 14,
                'description' => 'Une attaque lourde qui récompense la maîtrise.',
                'animation' => 'attack',
            ],
            'enemy-strike' => [
                'label' => 'Assaut',
                'type' => 'attack',
                'power' => 12,
                'mana_cost' => 0,
                'cooldown' => 0,
                'target' => 'enemy',
                'description' => 'Attaque standard adverse.',
                'animation' => 'attack',
            ],
            'enemy-crush' => [
                'label' => 'Percussion',
                'type' => 'attack',
                'power' => 18,
                'mana_cost' => 8,
                'cooldown' => 2,
                'target' => 'enemy',
                'description' => 'Un coup plus brutal.',
                'animation' => 'attack',
            ],
            'enemy-rally' => [
                'label' => 'Rage',
                'type' => 'buff',
                'mana_cost' => 10,
                'cooldown' => 3,
                'target' => 'self',
                'attack_bonus' => 5,
                'precision_bonus' => 6,
                'duration_rounds' => 2,
                'description' => 'Renforce brièvement la pression ennemie.',
                'animation' => 'cast',
            ],
        ],
        'hero_unlocks' => [
            'attaque-simple' => ['min_level' => 1, 'min_mastery' => 0],
            'defense-gardee' => ['min_level' => 1, 'min_mastery' => 0],
            'focus' => ['min_level' => 2, 'min_mastery' => 25],
            'soin-leger' => ['min_level' => 3, 'min_mastery' => 40],
            'heroic-burst' => ['min_level' => 4, 'min_mastery' => 60],
        ],
        'fallback_companion_skills' => [
            'attaque-simple',
            'defense-gardee',
            'focus',
            'soin-leger',
        ],
    ],
    'boss_phases' => [
        [
            'threshold' => 0.66,
            'label' => 'Rage montante',
            'effect' => [
                'attack_bonus' => 4,
                'precision_bonus' => 5,
                'duration_rounds' => 2,
            ],
            'message' => 'Le boss hausse le rythme et force la ligne.',
        ],
        [
            'threshold' => 0.33,
            'label' => 'Epreuve de maîtrise',
            'effect' => [
                'incoming_damage_multiplier' => 0.82,
                'duration_rounds' => 2,
            ],
            'message' => 'Le boss change de phase et tente de verrouiller le terrain.',
        ],
    ],
];
