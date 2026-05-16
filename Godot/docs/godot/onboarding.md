# Flow d'Onboarding Godot

## Architecture

```
scenes/onboarding/
├── onboarding_flow.tscn        # Container parent avec navigation
├── onboarding_flow.gd
└── steps/
    ├── step_welcome.tscn/.gd       # Étape 1 - Bienvenue
    ├── step_avatar_body.tscn/.gd   # Étape 2 - Choix du corps
    ├── step_avatar_hair.tscn/.gd   # Étape 3 - Choix cheveux
    ├── step_avatar_outfit.tscn/.gd # Étape 4 - Choix tenue
    ├── step_companion.tscn/.gd     # Étape 5 - Choix compagnon
    └── step_tutorial.tscn/.gd      # Étape 6 - Mini-tutoriel
```

## Déroulement

Welcome → Avatar (body) → Avatar (hair) → Avatar (outfit) → Companion starter → Tutoriel → Hub

## Navigation

- `Précédent` : retourne à l'étape précédente (caché sur la première)
- `Suivant` : passe à l'étape suivante (devient "Commencer l'aventure" au dernier)
- `Passer` : applique les valeurs par défaut et termine l'onboarding
- ProgressBar : 5 dots pour les étapes 2-6

## Activation du Mock Mode

### Méthode 1 : Fichier de config

Éditer `config/env.cfg` :
```ini
[debug]
mock_mode=true
```

### Méthode 2 : Scene de test

Ouvrir la scene `res://scenes/dev/test_onboarding.tscn` et cliquer sur
"Lancer onboarding (mock)". Cela active MockMode et lance le flow.

## Persistance

L'état est sauvegardé dans LocalStorage après chaque étape :

| Clé | Contenu |
|-----|---------|
| `onboarding_complete` | Booléen indiquant si l'onboarding est fini |
| `onboarding_step` | Index de la dernière étape visitée |
| `onboarding_avatar` | Dictionnaire des choix d'avatar |
| `onboarding_companion` | Slug du compagnon sélectionné |

Si le joueur quitte l'application en cours d'onboarding, il reprendra
à l'étape sauvegardée.

## Données Mock

Le fichier `mock/avatar_options.gd` contient toutes les données de test :

- **12 corps** : 4 féminins, 4 masculins, 4 neutres
- **10 coiffures** : courts, longs, bouclés, etc.
- **6 tenues** : casual, conducteur, sport, élégant, voyageur, mécanicien
- **8 teintes de peau**
- **10 couleurs de cheveux**
- **3 compagnons starters** : Gardien des Panneaux, Stratège des Priorités, Éclaireur de Vitesse

Les sprites sont des placeholders colorés (ColorRect). Remplacez-les par
de vraies textures quand les assets seront prêts.

## Ajouter une nouvelle étape

1. Créer le script `.gd` et la scene `.tscn` dans `scenes/onboarding/steps/`
2. Le script doit exposer :
   - `func set_data(data: Dictionary)` pour recevoir l'état
   - `func get_data() -> Dictionary` pour renvoyer les sélections
   - `signal data_changed(data: Dictionary)` pour les mises à jour live
3. Ajouter le chemin de la scene dans la constante `STEPS` de
   `onboarding_flow.gd`
4. Mettre à jour la gestion des données dans `_on_step_data_changed()` si
   nécessaire

## Intégration

L'onboarding est déclenché depuis le boot/login/register :

```gdscript
# Après login/register
if not OnboardingService.is_onboarded():
    SceneManager.change_scene("res://scenes/onboarding/onboarding_flow.tscn")
```

Depuis les settings, "Rejouer le tutoriel" appelle :
```gdscript
OnboardingService.reset()
SceneManager.change_scene("res://scenes/onboarding/onboarding_flow.tscn")
```
