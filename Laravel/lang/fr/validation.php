<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'attributes' => [
        'nickname' => 'pseudo',
        'body_asset' => 'morphologie',
        'hair_asset' => 'coiffure',
        'starter_choice' => 'classe',
        'colors.hair' => 'couleur de cheveux',
        'colors.skin' => 'couleur de peau',
    ],
];
