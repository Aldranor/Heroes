# Avatar Assets

Le système d’avatar a maintenant une source canonique : `resources/assets/avatar`.
Le runtime sert les fichiers via la route `avatar.assets.show`, avec fallback sur `public/avatar` tant que la migration complète n’est pas terminée.

## Structure

```text
resources/assets/avatar/
  bodies/
  hairs/
  back/
  bottom/
  chest/
  facialHair/
  feet/
  outfits/
```

## Règles générales

- Ajouter un asset = déposer un fichier dans le bon dossier
- Le catalogue le détecte automatiquement
- `config/avatar.php` ne sert qu’aux defaults, presets et overrides optionnels
- Les labels immersifs connus sont traduits via `lang/*/onboarding.php`

## Bodies

- Dossier : `resources/assets/avatar/bodies/`
- Convention recommandée : `skinXX.png`
- Preview par défaut : grille `48 x 48`, frame `0,0`

Pour un nouveau body :

1. ajouter le PNG dans `resources/assets/avatar/bodies/`
2. si besoin, ajouter sa teinte logique dans `config/avatar.php > body_skin_tones`
3. si tu veux un libellé immersif, ajouter `onboarding.avatar.body_profiles.skinXX` dans les fichiers `lang`

## Hairs

- Dossier : `resources/assets/avatar/hairs/`
- Convention : `MM_cVV.png`

Signification :

- `MM` = modèle de coiffure
- `cVV` = variante

Le système groupe automatiquement les coiffures par modèle.

Pour une nouvelle coiffure :

1. ajouter le PNG dans `resources/assets/avatar/hairs/`
2. si le modèle est nouveau, il apparaîtra automatiquement
3. si tu veux un nom RPG propre, ajouter `onboarding.avatar.hair_styles.MM` dans les fichiers `lang`

## Pièces de tenue

Les pièces sont auto-détectées par dossier :

- `back/` → `back`
- `bottom/` → `pants`
- `chest/` → `top`
- `facialHair/` → `facial_hair`
- `feet/` → `shoes`
- `outfits/` → `full_outfit`

Pour ajouter une nouvelle pièce :

1. déposer le PNG dans le bon dossier
2. respecter si possible la convention `MM_cVV.png`
3. ne rien changer au renderer

## Presets de tenue

Les presets restent configurés dans `config/avatar.php > outfit_presets`.

Exemple :

```php
'strategist' => [
    'label' => 'Stratège',
    'pieces' => [
        'back' => 'back_00_c00',
        'shoes' => 'feet_00_c00',
        'pants' => 'bottom_00_c00',
        'top' => 'chest_00_c00',
    ],
],
```

Rendu :

- si `full_outfit` existe, il remplace les autres slots
- sinon le renderer empile les couches dans l’ordre : `back`, `pants`, `shoes`, `top`, puis overlays

## Preview PNG

Le pack actuel utilise une spritesheet `288 x 192`, soit une grille `6 x 4` en frames `48 x 48`.

La config par défaut est donc :

```php
'preview' => [
    'frame_width' => 48,
    'frame_height' => 48,
    'col' => 0,
    'row' => 0,
],
```

Tu n’ajoutes un override que si un asset a besoin d’un cadrage particulier.
