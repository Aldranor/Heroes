<?php

return [
    'page_title' => 'Académie des Héros',
    'step_label' => 'Admission',
    'steps_total' => '3 étapes',
    'progress' => 'Étape :current sur :total',
    'brand_hint' => 'Académie des Héros',
    'default_nickname' => 'Votre héros',
    'class_placeholder' => 'Choisir un compagnon',
    'slider_previous' => 'Précédent',
    'slider_next' => 'Suivant',

    'preview' => [
        'eyebrow' => 'Aperçu',
        'title' => 'Votre héros',
        'class_prefix' => 'Compagnon',
        'hint' => 'Votre héros évolue au fil de vos missions.',
    ],

    'stepper' => [
        'avatar' => 'Héros',
        'class' => 'Compagnon',
        'summary' => 'Mission',
    ],

    'hero' => [
        'label' => 'Admission',
        'title' => 'Académie des Héros',
        'body_line_1' => 'Le monde est instable.',
        'body_line_2' => 'Des menaces apparaissent, et les héros se font rares.',
        'body_line_3' => 'L\'Académie forme une nouvelle génération d\'apprentis héros,',
        'body_line_4' => 'préparés à intervenir à travers des missions.',
        'body_line_5' => 'Progressez, gagnez en expérience',
        'body_line_6' => 'et devenez capable d\'agir quand tout bascule.',
        'cta_primary' => 'Rejoindre l\'académie',
        'cta_secondary' => 'Déjà étudiant ? Se connecter',
    ],

    'journey' => [
        'title' => 'Votre parcours',
        'subtitle' => 'Un entraînement progressif rythmé par des missions.',
        'train' => [
            'title' => 'S\'entraîner',
            'description' => 'Préparez-vous avec des leçons courtes et ciblées.',
        ],
        'mission' => [
            'title' => 'Partir en mission',
            'description' => 'Relevez des défis avec objectifs et validation immédiate.',
        ],
        'progress' => [
            'title' => 'Progresser',
            'description' => 'Gagnez de l\'expérience et débloquez de nouvelles missions.',
        ],
    ],

    'to_start' => [
        'title' => 'Pour commencer',
        'step_1' => 'Créer votre héros',
        'step_2' => 'Choisir votre compagnon',
        'step_3' => 'Lancer votre première mission',
    ],

    'avatar' => [
        'eyebrow' => 'Étape 1',
        'title' => 'Créez votre héros',
        'description' => 'Définissez votre identité avant votre première mission.',
        'nickname_label' => 'Nom de héros',
        'nickname_placeholder' => 'Ex. Nova',
        'nickname_help' => 'Votre nom sera visible dans l\'académie et en mission.',
        'body_label' => 'Silhouette',
        'body_help' => 'Choisissez l\'apparence de votre héros.',
        'hair_style_label' => 'Coiffure',
        'hair_style_help' => 'Sélectionnez un style et ajustez-le si nécessaire.',
        'hair_variant_label' => 'Variantes',
        'hair_variant_help' => 'Modifie uniquement le rendu de la coiffure.',
        'body_variant_label' => 'Variante',
        'body_variant_help' => 'Personnalisez l\'apparence globale.',
        'body_variant_original' => 'Original',
        'default_variant' => 'Standard',
        'next' => 'Suivant',

        // profils OK (déjà bien)
        'body_profiles' => [
            'human-female' => [
                'label' => 'Humaine',
                'description' => 'Agile et équilibrée',
            ],
            'human-male' => [
                'label' => 'Humain',
                'description' => 'Stable et adaptable',
            ],
            'human-male-muscular' => [
                'label' => 'Colosse',
                'description' => 'Imposant et robuste',
            ],
            'lizard-female' => [
                'label' => 'Saurienne',
                'description' => 'Instinctive et vive',
            ],
            'lizard-male' => [
                'label' => 'Saurien',
                'description' => 'Sec et tenace',
            ],
            'wolf-female' => [
                'label' => 'Louve',
                'description' => 'Rapide et féroce',
            ],
            'wolf-male' => [
                'label' => 'Loup',
                'description' => 'Brutal et endurant',
            ],
        ],

        // hair + colors OK → déjà très bien, cohérent RPG stylisé
    ],

    'class' => [
        'eyebrow' => 'Étape 2',
        'title' => 'Choisissez votre compagnon',
        'description' => 'Votre compagnon vous accompagnera lors de vos premières missions.',
        'back' => 'Retour',
        'next' => 'Suivant',
        'hint' => 'Vous débutez avec trois compagnons. Vous pourrez tous les entraîner par la suite.',
        'options' => [
            'stratege' => [
                'icon' => 'ST',
                'title' => 'Stratège',
                'description' => 'Anticipe et planifie',
                'identity' => 'Analyse les situations et sécurise vos décisions.',
                'outfit_label' => 'Tenue Stratège',
            ],
            'observateur' => [
                'icon' => 'OB',
                'title' => 'Observateur',
                'description' => 'Repère les détails',
                'identity' => 'Détecte les signaux clés et évite les erreurs.',
                'outfit_label' => 'Tenue Observateur',
            ],
            'eclaireur' => [
                'icon' => 'EC',
                'title' => 'Éclaireur',
                'description' => 'Agit rapidement',
                'identity' => 'Teste, avance et s’adapte en continu.',
                'outfit_label' => 'Tenue Éclaireur',
            ],
        ],
    ],

    'summary' => [
        'eyebrow' => 'Étape 3',
        'title' => 'Prêt pour votre première mission ?',
        'description' => 'Vérifiez votre profil, puis partez en mission.',
        'chosen_class' => 'Compagnon choisi',
        'team_title' => 'Compagnons',
        'team_hint' => 'Vous pourrez entraîner toute votre équipe.',
        'back' => 'Retour',
        'submit' => 'Lancer votre première mission',
    ],

    'micro_copy' => [
        'xp_gained' => '+:xp XP',
        'mission_success' => 'Mission réussie',
        'mission_unlocked' => 'Nouvelle mission débloquée',
        'level_up' => 'Niveau supérieur atteint',
    ],
];
