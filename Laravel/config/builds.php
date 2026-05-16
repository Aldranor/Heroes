<?php

return [
    'loadout_limits' => [
        'active' => 4,
        'passive' => 3,
        'ultimate' => 1,
    ],

    'respec' => [
        'hero_coin_cost' => 150,
        'companion_coin_cost' => 90,
        'free_uses' => 1,
    ],

    'roles' => [
        'tank' => [
            'label' => 'Tank',
            'description' => 'Encaisse, protège et tient la ligne.',
            'stat_modifiers' => [
                'hp_multiplier' => 1.18,
                'defense_bonus' => 4,
                'resistance_bonus' => 4,
                'speed_bonus' => -1,
            ],
        ],
        'dps' => [
            'label' => 'DPS',
            'description' => 'Maximise la pression offensive.',
            'stat_modifiers' => [
                'attack_bonus' => 5,
                'magic_attack_bonus' => 5,
                'crit_bonus' => 4,
            ],
        ],
        'support' => [
            'label' => 'Support',
            'description' => 'Soigne, renforce et stabilise le groupe.',
            'stat_modifiers' => [
                'mana_bonus' => 12,
                'healing_power_multiplier' => 1.18,
                'defense_bonus' => 1,
            ],
        ],
        'control' => [
            'label' => 'Contrôle',
            'description' => 'Ralentit, marque et casse le rythme adverse.',
            'stat_modifiers' => [
                'precision_bonus' => 6,
                'speed_bonus' => 2,
                'mana_bonus' => 8,
            ],
        ],
        'hybrid' => [
            'label' => 'Hybride',
            'description' => 'Polyvalent et adaptable.',
            'stat_modifiers' => [
                'attack_bonus' => 2,
                'magic_attack_bonus' => 2,
                'defense_bonus' => 2,
                'mana_bonus' => 4,
            ],
        ],
    ],

    'classes' => [
        'strategist' => [
            'label' => 'Stratège',
            'default_role' => 'hybrid',
            'specializations' => [
                'tactician' => [
                    'label' => 'Tacticien',
                    'role' => 'control',
                    'focus_tags' => ['control', 'support', 'ranged', 'mental'],
                    'stat_modifiers' => [
                        'precision_bonus' => 8,
                        'speed_bonus' => 2,
                    ],
                ],
                'analyst' => [
                    'label' => 'Analyste',
                    'role' => 'hybrid',
                    'focus_tags' => ['magical', 'mental', 'light', 'control'],
                    'stat_modifiers' => [
                        'magic_attack_bonus' => 6,
                        'mana_bonus' => 8,
                    ],
                ],
                'support' => [
                    'label' => 'Soutien',
                    'role' => 'support',
                    'focus_tags' => ['support', 'shield', 'light', 'energy'],
                    'stat_modifiers' => [
                        'healing_power_multiplier' => 1.15,
                        'defense_bonus' => 2,
                    ],
                ],
            ],
        ],
        'observer' => [
            'label' => 'Observateur',
            'default_role' => 'dps',
            'specializations' => [
                'shooter' => [
                    'label' => 'Tireur',
                    'role' => 'dps',
                    'focus_tags' => ['physical', 'ranged', 'bow', 'crit'],
                    'stat_modifiers' => [
                        'attack_bonus' => 6,
                        'precision_bonus' => 6,
                    ],
                ],
                'scout' => [
                    'label' => 'Éclaireur',
                    'role' => 'hybrid',
                    'focus_tags' => ['physical', 'ranged', 'dagger', 'control'],
                    'stat_modifiers' => [
                        'speed_bonus' => 4,
                        'precision_bonus' => 3,
                    ],
                ],
                'sniper' => [
                    'label' => 'Sniper',
                    'role' => 'dps',
                    'focus_tags' => ['physical', 'ranged', 'bow', 'crit'],
                    'stat_modifiers' => [
                        'attack_bonus' => 7,
                        'crit_bonus' => 6,
                        'speed_bonus' => -1,
                    ],
                ],
            ],
        ],
        'guardian' => [
            'label' => 'Gardien',
            'default_role' => 'tank',
            'specializations' => [
                'protector' => [
                    'label' => 'Protecteur',
                    'role' => 'tank',
                    'focus_tags' => ['physical', 'melee', 'shield', 'support'],
                    'stat_modifiers' => [
                        'hp_multiplier' => 1.16,
                        'defense_bonus' => 5,
                    ],
                ],
                'breaker' => [
                    'label' => 'Briseur',
                    'role' => 'dps',
                    'focus_tags' => ['physical', 'melee', 'sword', 'control'],
                    'stat_modifiers' => [
                        'attack_bonus' => 7,
                        'defense_bonus' => 1,
                    ],
                ],
                'counter' => [
                    'label' => 'Contre-attaquant',
                    'role' => 'hybrid',
                    'focus_tags' => ['physical', 'melee', 'counter', 'crit'],
                    'stat_modifiers' => [
                        'defense_bonus' => 3,
                        'crit_bonus' => 4,
                    ],
                ],
            ],
        ],
    ],

    'tag_labels' => [
        'physical' => 'Physique',
        'magical' => 'Magique',
        'melee' => 'CAC',
        'ranged' => 'Distance',
        'support' => 'Support',
        'control' => 'Contrôle',
        'fire' => 'Feu',
        'ice' => 'Glace',
        'mental' => 'Mental',
        'light' => 'Lumière',
        'energy' => 'Énergie',
        'shield' => 'Bouclier',
        'crit' => 'Critique',
        'poison' => 'Poison',
        'sword' => 'Épée',
        'spear' => 'Lance',
        'dagger' => 'Dagues',
        'bow' => 'Arc',
        'ultimate' => 'Ultime',
        'hybrid' => 'Hybride',
        'mark' => 'Marque',
        'counter' => 'Riposte',
    ],

    'party_synergies' => [
        [
            'key' => 'shielded_backline',
            'label' => 'Écran défensif',
            'description' => 'Un tank couvre la ligne arrière.',
            'requirements' => [
                'roles' => ['tank'],
                'actor_tags' => ['ranged'],
            ],
            'effects' => [
                [
                    'target' => 'matching_actors',
                    'match_actor_tags' => ['ranged'],
                    'stat' => 'incoming_damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 0.9,
                ],
                [
                    'target' => 'matching_actors',
                    'match_actor_tags' => ['ranged'],
                    'stat' => 'defense_bonus',
                    'mode' => 'add',
                    'value' => 2,
                ],
            ],
        ],
        [
            'key' => 'focused_fire',
            'label' => 'Escouade feu',
            'description' => 'Plusieurs compétences feu amplifient les brûlures.',
            'requirements' => [
                'skill_tags_count' => [
                    'fire' => 2,
                ],
            ],
            'effects' => [
                [
                    'target' => 'all',
                    'match_skill_tags' => ['fire'],
                    'stat' => 'damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.15,
                ],
            ],
        ],
        [
            'key' => 'full_physical',
            'label' => 'Compo physique',
            'description' => 'Une équipe majoritairement physique enfonce la garde.',
            'requirements' => [
                'actor_tags_count' => [
                    'physical' => 3,
                ],
            ],
            'effects' => [
                [
                    'target' => 'all',
                    'match_skill_tags' => ['physical'],
                    'stat' => 'damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.12,
                ],
            ],
        ],
        [
            'key' => 'frontline_pressure',
            'label' => 'Pression CAC',
            'description' => 'Deux profils ou plus au corps à corps forcent un tempo plus agressif.',
            'requirements' => [
                'actor_tags_count' => [
                    'melee' => 2,
                ],
            ],
            'effects' => [
                [
                    'target' => 'all',
                    'match_skill_tags' => ['melee'],
                    'stat' => 'damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.1,
                ],
            ],
        ],
        [
            'key' => 'mark_and_burst',
            'label' => 'Marquage coordonné',
            'description' => 'Les équipes contrôle + DPS exploitent mieux les ouvertures.',
            'requirements' => [
                'roles' => ['control', 'dps'],
            ],
            'effects' => [
                [
                    'target' => 'all',
                    'match_skill_tags' => ['mark'],
                    'stat' => 'precision_bonus',
                    'mode' => 'add',
                    'value' => 6,
                ],
                [
                    'target' => 'all',
                    'match_skill_tags' => ['control'],
                    'stat' => 'damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.08,
                ],
            ],
        ],
        [
            'key' => 'arcane_mesh',
            'label' => 'Maillage arcanique',
            'description' => 'Les équipes magiques contrôle/support font mieux circuler l’énergie.',
            'requirements' => [
                'skill_tags_count' => [
                    'magical' => 3,
                ],
                'roles' => ['control', 'support'],
            ],
            'effects' => [
                [
                    'target' => 'all',
                    'match_skill_tags' => ['magical'],
                    'stat' => 'damage_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.1,
                ],
                [
                    'target' => 'all',
                    'match_skill_tags' => ['support'],
                    'stat' => 'healing_multiplier',
                    'mode' => 'multiply',
                    'value' => 1.12,
                ],
            ],
        ],
        [
            'key' => 'shield_cycle',
            'label' => 'Cycle défensif',
            'description' => 'Les équipes tank/support optimisent mieux les compétences de bouclier.',
            'requirements' => [
                'roles' => ['tank', 'support'],
                'skill_tags_count' => [
                    'shield' => 2,
                ],
            ],
            'effects' => [
                [
                    'target' => 'matching_actors',
                    'match_actor_tags' => ['shield'],
                    'stat' => 'defense_bonus',
                    'mode' => 'add',
                    'value' => 2,
                ],
                [
                    'target' => 'all',
                    'match_skill_tags' => ['shield'],
                    'stat' => 'resource_on_shield',
                    'mode' => 'add',
                    'value' => 2,
                ],
            ],
        ],
    ],
];
