extends Control

signal step_next()

@onready var _root_vbox: VBoxContainer = %RootVBox
@onready var _brand_label: Label = %BrandLabel
@onready var _login_button: AppButton = %LoginButton
@onready var _hero_card: Node = %HeroCard
@onready var _journey_panel: Node = %JourneyPanel
@onready var _content: VBoxContainer = %Content

const HERO_REWARD_LINES: PackedStringArray = ["+120 XP", "MISSION RÉUSSIE", "NOUVELLE MISSION DÉBLOQUÉE", "NIVEAU SUPÉRIEUR ATTEINT"]
const JOURNEY_ITEMS: Array[Dictionary] = [
	{"kicker": "S’ENTRAÎNER", "title": "Modules courts", "desc": "Préparation rapide avant chaque départ."},
	{"kicker": "PARTIR EN MISSION", "title": "Défis interactifs", "desc": "Objectifs clairs et validation immédiate."},
	{"kicker": "PROGRESSER", "title": "Expérience et déblocages", "desc": "Suivi des performances et nouveaux contenus."},
]

func _ready() -> void:
	_style_brand_bar()
	_configure_components()
	_wire_actions()
	modulate = Color(1, 1, 1, 0)
	_play_enter_animation()

func _style_brand_bar() -> void:
	_brand_label.theme_type_variation = "LabelBrand"

func _wire_actions() -> void:
	_hero_card.cta_pressed.connect(_on_start_pressed)
	_hero_card.login_pressed.connect(_on_login_pressed)
	_login_button.pressed.connect(_on_login_pressed)

func _configure_components() -> void:
	_hero_card.setup({
		"eyebrow_text": "ADMISSION",
		"title_text": "Académie des Héros",
		"description_text": "Le monde devient instable et les héros manquent de préparation. Rejoignez un entraînement structuré par missions, gagnez de l’expérience et montez en puissance à chaque validation.",
		"cta_text": "REJOINDRE L'ACADÉMIE",
		"login_text": "Se connecter",
		"reward_lines": HERO_REWARD_LINES
	})
	_journey_panel.setup({
		"eyebrow_text": "VOTRE PARCOURS",
		"title_text": "Un système d’entraînement progressif basé sur des missions.",
		"journey_items": JOURNEY_ITEMS
	})

func _on_start_pressed() -> void:
	if AudioManager != null:
		AudioManager.play_sfx("click", -5.0)
	step_next.emit()

func _on_login_pressed() -> void:
	if AudioManager != null:
		AudioManager.play_sfx("click", -8.0)
	SceneManager.change_scene(SceneManager.LOGIN, SceneManager.TransitionStyle.FADE)

func _play_enter_animation() -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_QUART)
	modulate = Color(1, 1, 1, 0)
	_root_vbox.scale = Vector2(0.98, 0.98)
	tween.tween_property(self, "modulate", Color(1, 1, 1, 1), 0.35)
	tween.parallel().tween_property(_root_vbox, "scale", Vector2(1, 1), 0.35)

func play_exit_animation() -> Tween:
	var tween: Tween = create_tween().set_ease(Tween.EASE_IN).set_trans(Tween.TRANS_QUART)
	tween.tween_property(self, "modulate", Color(1, 1, 1, 0), 0.2)
	return tween
