<?php

$castSequence = range(0, 6);
$thrustSequence = range(0, 7);
$idleSequence = [0];
$walkSequence = range(0, 7);
$slashSequence = range(0, 5);
$shootSequence = range(0, 12);
$hurtSequence = range(0, 5);
$twoFrameSequence = [0, 1];
$threeFrameSequence = [0, 1, 2];
$fiveFrameSequence = [0, 1, 2, 3, 4];

return [
    'profiles' => [
        'lpc' => [
            'meta' => [
                'frame_width' => 64,
                'frame_height' => 64,
                'columns' => 13,
            ],
            'animations' => [
                'cast' => [
                    'fps' => 10,
                    'loop' => false,
                    'sequence' => $castSequence,
                    'directions' => [
                        'back' => ['row' => 0, 'start_column' => 0],
                        'left' => ['row' => 1, 'start_column' => 0],
                        'front' => ['row' => 2, 'start_column' => 0],
                        'right' => ['row' => 3, 'start_column' => 0],
                    ],
                ],
                'thrust' => [
                    'fps' => 12,
                    'loop' => false,
                    'sequence' => $thrustSequence,
                    'directions' => [
                        'back' => ['row' => 4, 'start_column' => 0],
                        'left' => ['row' => 5, 'start_column' => 0],
                        'front' => ['row' => 6, 'start_column' => 0],
                        'right' => ['row' => 7, 'start_column' => 0],
                    ],
                ],
                'idle' => [
                    'fps' => 6,
                    'loop' => true,
                    'sequence' => $idleSequence,
                    'directions' => [
                        'back' => ['row' => 8, 'start_column' => 0],
                        'left' => ['row' => 9, 'start_column' => 0],
                        'front' => ['row' => 10, 'start_column' => 0],
                        'right' => ['row' => 11, 'start_column' => 0],
                    ],
                ],
                'walk' => [
                    'fps' => 10,
                    'loop' => true,
                    'sequence' => $walkSequence,
                    'directions' => [
                        'back' => ['row' => 8, 'start_column' => 0],
                        'left' => ['row' => 9, 'start_column' => 0],
                        'front' => ['row' => 10, 'start_column' => 0],
                        'right' => ['row' => 11, 'start_column' => 0],
                    ],
                ],
                'slash' => [
                    'fps' => 12,
                    'loop' => false,
                    'sequence' => $slashSequence,
                    'directions' => [
                        'back' => ['row' => 12, 'start_column' => 0],
                        'left' => ['row' => 13, 'start_column' => 0],
                        'front' => ['row' => 14, 'start_column' => 0],
                        'right' => ['row' => 15, 'start_column' => 0],
                    ],
                ],
                'shoot' => [
                    'fps' => 12,
                    'loop' => false,
                    'sequence' => $shootSequence,
                    'directions' => [
                        'back' => ['row' => 16, 'start_column' => 0],
                        'left' => ['row' => 17, 'start_column' => 0],
                        'front' => ['row' => 18, 'start_column' => 0],
                        'right' => ['row' => 19, 'start_column' => 0],
                    ],
                ],
                'hurt' => [
                    'fps' => 8,
                    'loop' => false,
                    'sequence' => $hurtSequence,
                    'directions' => [
                        'back' => ['row' => 20, 'start_column' => 0],
                        'left' => ['row' => 20, 'start_column' => 0],
                        'front' => ['row' => 20, 'start_column' => 0],
                        'right' => ['row' => 20, 'start_column' => 0],
                    ],
                ],

                // Runtime aliases used by the combat screen for standard 21-row sheets.
                'combat_idle' => [
                    'fps' => 4,
                    'loop' => true,
                    'sequence' => $walkSequence,
                    'directions' => [
                        'back' => ['row' => 8, 'start_column' => 9],
                        'left' => ['row' => 9, 'start_column' => 0],
                        'front' => ['row' => 10, 'start_column' => 0],
                        'right' => ['row' => 11, 'start_column' => 0],
                    ],
                ],
                'combat_attack' => [
                    'fps' => 12,
                    'loop' => false,
                    'sequence' => $slashSequence,
                    'directions' => [
                        'back' => ['row' => 12, 'start_column' => 0],
                        'left' => ['row' => 13, 'start_column' => 0],
                        'front' => ['row' => 14, 'start_column' => 0],
                        'right' => ['row' => 13, 'start_column' => 0],
                    ],
                ],
                'dead' => [
                    'fps' => 8,
                    'loop' => false,
                    'sequence' => [0],
                    'directions' => [
                        'back' => ['row' => 20, 'start_column' => 0],
                        'left' => ['row' => 20, 'start_column' => 0],
                        'front' => ['row' => 20, 'start_column' => 0],
                        'right' => ['row' => 20, 'start_column' => 0],
                    ],
                ],
            ],
            'extensions' => [
                [
                    'match' => [
                        'min_rows' => 54,
                    ],
                    // The extended companion sheet keeps the universal LPC base
                    // up to row 20, then adds LPC Expanded / ULPC-style rows
                    // after it. Naming below follows those animation families.
                    'animations' => [
                        // This sheet only exposes a single climb strip.
                        'climb' => [
                            'fps' => 8,
                            'loop' => true,
                            'sequence' => $hurtSequence,
                            'directions' => [
                                'back' => ['row' => 21, 'start_column' => 0],
                                'left' => ['row' => 21, 'start_column' => 0],
                                'front' => ['row' => 21, 'start_column' => 0],
                                'right' => ['row' => 21, 'start_column' => 0],
                            ],
                        ],
                        'animated_idle' => [
                            'fps' => 4,
                            'loop' => true,
                            'sequence' => $twoFrameSequence,
                            'directions' => [
                                'back' => ['row' => 22, 'start_column' => 0],
                                'left' => ['row' => 23, 'start_column' => 0],
                                'front' => ['row' => 24, 'start_column' => 0],
                                'right' => ['row' => 25, 'start_column' => 0],
                            ],
                        ],
                        'jump' => [
                            'fps' => 8,
                            'loop' => false,
                            'sequence' => $fiveFrameSequence,
                            'directions' => [
                                'back' => ['row' => 26, 'start_column' => 0],
                                'left' => ['row' => 27, 'start_column' => 0],
                                'front' => ['row' => 28, 'start_column' => 0],
                                'right' => ['row' => 29, 'start_column' => 0],
                            ],
                        ],
                        'sit' => [
                            'fps' => 6,
                            'loop' => false,
                            'sequence' => $threeFrameSequence,
                            'directions' => [
                                'back' => ['row' => 30, 'start_column' => 0],
                                'left' => ['row' => 31, 'start_column' => 0],
                                'front' => ['row' => 32, 'start_column' => 0],
                                'right' => ['row' => 33, 'start_column' => 0],
                            ],
                        ],
                        'emote' => [
                            'fps' => 6,
                            'loop' => false,
                            'sequence' => $threeFrameSequence,
                            'directions' => [
                                'back' => ['row' => 34, 'start_column' => 0],
                                'left' => ['row' => 35, 'start_column' => 0],
                                'front' => ['row' => 36, 'start_column' => 0],
                                'right' => ['row' => 37, 'start_column' => 0],
                            ],
                        ],
                        'run' => [
                            'fps' => 10,
                            'loop' => true,
                            'sequence' => $walkSequence,
                            'directions' => [
                                'back' => ['row' => 38, 'start_column' => 0],
                                'left' => ['row' => 39, 'start_column' => 0],
                                'front' => ['row' => 40, 'start_column' => 0],
                                'right' => ['row' => 41, 'start_column' => 0],
                            ],
                        ],
                        'combat' => [
                            'fps' => 4,
                            'loop' => true,
                            'sequence' => [0, 0, 1],
                            'directions' => [
                                'back' => ['row' => 42, 'start_column' => 0],
                                'left' => ['row' => 43, 'start_column' => 0],
                                'front' => ['row' => 44, 'start_column' => 0],
                                'right' => ['row' => 45, 'start_column' => 0],
                            ],
                        ],
                        // The expanded companion sheet exposes one long revised
                        // 1H attack strip, then a shorter halfslash strip.
                        'one_handed_slash' => [
                            'fps' => 12,
                            'loop' => false,
                            'sequence' => $shootSequence,
                            'directions' => [
                                'back' => ['row' => 46, 'start_column' => 0],
                                'left' => ['row' => 47, 'start_column' => 0],
                                'front' => ['row' => 48, 'start_column' => 0],
                                'right' => ['row' => 49, 'start_column' => 0],
                            ],
                        ],
                        'one_handed_halfslash' => [
                            'fps' => 12,
                            'loop' => false,
                            'sequence' => $slashSequence,
                            'directions' => [
                                'back' => ['row' => 50, 'start_column' => 0],
                                'left' => ['row' => 51, 'start_column' => 0],
                                'front' => ['row' => 52, 'start_column' => 0],
                                'right' => ['row' => 53, 'start_column' => 0],
                            ],
                        ],
                        'combat_attack' => [
                            'fps' => 12,
                            'loop' => false,
                            'sequence' => $slashSequence,
                            'directions' => [
                                'back' => ['row' => 50, 'start_column' => 0],
                                'left' => ['row' => 51, 'start_column' => 0],
                                'front' => ['row' => 52, 'start_column' => 0],
                                'right' => ['row' => 53, 'start_column' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
