<?php

return [
    'source_roots' => [
        resource_path('assets/avatar'),
        resource_path('assets'),
        public_path('avatar'),
    ],

    'preview' => [
        'frame_width' => 64,
        'frame_height' => 64,
        'col' => 0,
        'row' => 0,
    ],

    'asset_directories' => [
        'body' => 'bodies',
        'hair' => 'hairs',
        'outfit' => 'outfits',
    ],

    'wearable_slots' => [
        'full_outfit',
        'back',
        'shoes',
        'pants',
        'facial_hair',
        'helmet',
        'top',
        'accessory',
    ],

    'defaults' => [
        'body' => 'human-female',
        'hair' => 'bob--female--black',
        'outfit' => null,
        'outfit_preset' => null,
        'skin' => '#F7D7C4',
        'hair_color' => '#1E1E1E',
    ],

    'body_defaults' => [
        'description' => 'Une silhouette prête pour la route.',
        'type' => 'sheet_png',
        'preview_sheet' => 'Idle.png',
        'preview' => [
            'frame_width' => 64,
            'frame_height' => 64,
            'col' => 0,
            'row' => 2,
        ],
        'hair_scale' => '1.00',
        'hair_offset_y' => '0%',
    ],

    'body_profiles' => [
        'human-female' => [
            'path' => 'Human/Female',
            'label' => 'Humaine',
            'description' => 'Agile et équilibrée',
            'frame_profile' => 'female',
            'sort_order' => 10,
        ],
        'human-male' => [
            'path' => 'Human/Male',
            'label' => 'Humain',
            'description' => 'Stable et adaptable',
            'frame_profile' => 'male',
            'sort_order' => 20,
        ],
        'human-male-muscular' => [
            'path' => 'Human/Male Muscular',
            'label' => 'Colosse',
            'description' => 'Imposant et robuste',
            'frame_profile' => 'male',
            'sort_order' => 30,
        ],
        'lizard-female' => [
            'path' => 'Humanoid Animals/Lizardman/Female',
            'label' => 'Saurienne',
            'description' => 'Instinctive et vive',
            'frame_profile' => 'female',
            'sort_order' => 40,
        ],
        'lizard-male' => [
            'path' => 'Humanoid Animals/Lizardman/Male',
            'label' => 'Saurien',
            'description' => 'Sec et tenace',
            'frame_profile' => 'male',
            'sort_order' => 50,
        ],
        'wolf-female' => [
            'path' => 'Humanoid Animals/Wolfman/Female',
            'label' => 'Louve',
            'description' => 'Rapide et féroce',
            'frame_profile' => 'female',
            'sort_order' => 60,
        ],
        'wolf-male' => [
            'path' => 'Humanoid Animals/Wolfman/Male',
            'label' => 'Loup',
            'description' => 'Brutal et endurant',
            'frame_profile' => 'male',
            'sort_order' => 70,
        ],
    ],

    'hair_defaults' => [
        'description' => 'Choisis une coupe qui donne le ton.',
        'preview' => [
            'frame_width' => 64,
            'frame_height' => 64,
            'col' => 0,
            'row' => 2,
        ],
        'preferred_variant' => 'black',
        'supported_profiles' => ['female', 'male'],
        'generic_sources' => [
            'female' => [],
            'male' => [],
        ],
    ],

    'hair_models' => [
        'afro' => ['sort_order' => 10],
        'balding' => ['sort_order' => 11],
        'bangs-bun' => ['sort_order' => 12],
        'bob' => ['sort_order' => 20],
        'bob-side-part' => ['sort_order' => 30],
        'braid' => ['sort_order' => 40],
        'braid2' => ['sort_order' => 41],
        'buzzcut' => ['sort_order' => 50],
        'cornrows' => ['sort_order' => 51],
        'cowlick' => ['sort_order' => 52],
        'cowlick-tall' => ['sort_order' => 53],
        'curly-long' => ['sort_order' => 60],
        'curly-short' => ['sort_order' => 70],
        'curtains' => ['sort_order' => 75],
        'curtains-long' => ['sort_order' => 76],
        'dreadlocks-long' => ['sort_order' => 77],
        'dreadlocks-short' => ['sort_order' => 78],
        'flat-top-fade' => ['sort_order' => 79],
        'flat-top-straight' => ['sort_order' => 80],
        'half-up' => ['sort_order' => 81],
        'halfmessy' => ['sort_order' => 82],
        'high-and-tight' => ['sort_order' => 83],
        'high-ponytail' => ['sort_order' => 84],
        'idol' => ['sort_order' => 85],
        'lob' => ['sort_order' => 86],
        'long-band' => ['sort_order' => 87],
        'long-center-part' => ['sort_order' => 88],
        'long-messy' => ['sort_order' => 89],
        'long-messy2' => ['sort_order' => 90],
        'long-tied' => ['sort_order' => 91],
        'messy3' => ['sort_order' => 92],
        'mop' => ['sort_order' => 100],
        'natural' => ['sort_order' => 101],
        'part2' => ['sort_order' => 110],
        'pigtails' => ['sort_order' => 120],
        'pigtails-bangs' => ['sort_order' => 121],
        'sara' => ['sort_order' => 130],
        'spiked' => ['sort_order' => 140],
        'spiked2' => ['sort_order' => 141],
        'spiked-beehive' => ['sort_order' => 142],
        'spiked-liberty' => ['sort_order' => 143],
        'spiked-liberty2' => ['sort_order' => 144],
        'spiked-porcupine' => ['sort_order' => 145],
        'twists-fade' => ['sort_order' => 150],
        'twists-straight' => ['sort_order' => 151],
    ],

    'wearable_roots' => [
        'cape' => [
            'slot' => 'back',
            'sort_order' => 10,
        ],
        'feet' => [
            'slot' => 'shoes',
            'sort_order' => 20,
        ],
        'legs' => [
            'slot' => 'pants',
            'sort_order' => 30,
        ],
        'torso' => [
            'slot' => 'top',
            'sort_order' => 40,
        ],
        'facialHair' => [
            'slot' => 'facial_hair',
            'sort_order' => 50,
        ],
        'gloves' => [
            'slot' => 'accessory',
            'sort_order' => 60,
        ],
        'bauldron' => [
            'slot' => 'accessory',
            'sort_order' => 70,
        ],
        'arms' => [
            'slot' => 'accessory',
            'sort_order' => 80,
        ],
        'outfits' => [
            'slot' => 'full_outfit',
            'sort_order' => 90,
        ],
    ],

    'wearable_profile_aliases' => [
        'female' => 'female',
        'male' => 'male',
        'adult' => 'adult',
    ],

    'wearable_assets' => [],
    'outfit_assets' => [],

    'outfit_presets' => [
        'strategist' => [
            'label' => 'Stratège',
            'sort_order' => 10,
            'profiles' => [
                'female' => [
                    'pieces' => [
                        'back' => 'cape-solid-female-blue',
                        'shoes' => 'feet-boots-female-blue',
                        'pants' => 'legs-pants-female-blue',
                        'top' => 'torso-clothes-tunic-female-blue',
                    ],
                ],
                'male' => [
                    'pieces' => [
                        'back' => 'cape-solid-male-blue',
                        'shoes' => 'feet-boots-male-blue',
                        'pants' => 'legs-pants-male-blue',
                        'top' => 'torso-clothes-longsleeve-male-blue',
                    ],
                ],
            ],
        ],
        'observer' => [
            'label' => 'Observateur',
            'sort_order' => 20,
            'profiles' => [
                'female' => [
                    'pieces' => [
                        'back' => 'cape-solid-female-green',
                        'shoes' => 'feet-boots-female-green',
                        'pants' => 'legs-pants-female-green',
                        'top' => 'torso-clothes-tunic-female-green',
                    ],
                ],
                'male' => [
                    'pieces' => [
                        'back' => 'cape-solid-male-green',
                        'shoes' => 'feet-boots-male-green',
                        'pants' => 'legs-pants-male-green',
                        'top' => 'torso-clothes-longsleeve-male-green',
                    ],
                ],
            ],
        ],
        'scout' => [
            'label' => 'Éclaireur',
            'sort_order' => 30,
            'profiles' => [
                'female' => [
                    'pieces' => [
                        'back' => 'cape-solid-female-orange',
                        'shoes' => 'feet-boots-female-orange',
                        'pants' => 'legs-pants-female-orange',
                        'top' => 'torso-clothes-tunic-female-orange',
                    ],
                ],
                'male' => [
                    'pieces' => [
                        'back' => 'cape-solid-male-red',
                        'shoes' => 'feet-boots-male-orange',
                        'pants' => 'legs-pants-male-orange',
                        'top' => 'torso-clothes-longsleeve-male-orange',
                    ],
                ],
            ],
        ],
    ],

    'class_outfits' => [
        'stratege' => [
            'preset' => 'strategist',
            'accent' => '#5B6CFF',
        ],
        'observateur' => [
            'preset' => 'observer',
            'accent' => '#28A66A',
        ],
        'eclaireur' => [
            'preset' => 'scout',
            'accent' => '#FF8A3D',
        ],
    ],
];
