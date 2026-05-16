extends Control

signal step_next()

@onready var _logo_label: Label = %LogoLabel
@onready var _subtitle_label: Label = %SubtitleLabel
@onready var _description_label: Label = %DescriptionLabel
@onready var _hero_sprite: TextureRect = %HeroSprite
@onready var _start_button: Button = %StartButton
@onready var _container: VBoxContainer = %Container

func _ready() -> void:
	modulate = Color(1, 1, 1, 0)
	_start_button.pressed.connect(_on_start_pressed)
	_play_enter_animation()

func _on_start_pressed() -> void:
	AudioManager.play_sfx("click", -5.0)
	step_next.emit()

func _play_enter_animation() -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_QUART)
	modulate = Color(1, 1, 1, 0)
	_container.scale = Vector2(0.85, 0.85)
	tween.tween_property(self, "modulate", Color(1, 1, 1, 1), 0.6)
	tween.parallel().tween_property(_container, "scale", Vector2(1, 1), 0.6)

func play_exit_animation() -> Tween:
	var tween: Tween = create_tween().set_ease(Tween.EASE_IN).set_trans(Tween.TRANS_QUART)
	tween.tween_property(self, "modulate", Color(1, 1, 1, 0), 0.3)
	tween.parallel().tween_property(_container, "scale", Vector2(0.85, 0.85), 0.3)
	return tween
