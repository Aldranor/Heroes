extends Control

signal step_next()

@onready var _root_vbox: VBoxContainer = %RootVBox
@onready var _brand_label: Label = %BrandLabel
@onready var _login_button: AppButton = %LoginButton
@onready var _hero_card: AppOnboardingHeroCard = %HeroCard
@onready var _journey_panel: AppOnboardingJourneyPanel = %JourneyPanel
@onready var _content: VBoxContainer = %Content

const FONT_BODY: Font = preload("res://assets/fonts/inter_medium.tres")

func _ready() -> void:
	_style_brand_bar()
	_wire_actions()
	modulate = Color(1, 1, 1, 0)
	_play_enter_animation()

func _style_brand_bar() -> void:
	_brand_label.add_theme_font_override("font", FONT_BODY)
	_brand_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_SM)
	_brand_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)

func _wire_actions() -> void:
	_hero_card.cta_pressed.connect(_on_start_pressed)
	_hero_card.login_pressed.connect(_on_login_pressed)
	_login_button.pressed.connect(_on_login_pressed)

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
