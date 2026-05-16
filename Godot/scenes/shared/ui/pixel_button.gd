extends Button
class_name PixelButton

enum ButtonType {
	PRIMARY,
	SECONDARY,
	DANGER
}

const RPG_BUTTONS_TEXTURE: Texture2D = preload("res://assets/sprites/ui/rpg/Buttons.png")

@export var button_type: ButtonType = ButtonType.PRIMARY:
	set(value):
		button_type = value
		_apply_variant()

@export var use_rpg_assets: bool = false:
	set(value):
		use_rpg_assets = value
		if is_node_ready():
			_apply_variant()

var _press_tween: Tween
var _hover_tween: Tween
var _base_modulate: Color = Color(1, 1, 1, 1)

func _ready() -> void:
	custom_minimum_size = Vector2(44, 44)
	pivot_offset = size * 0.5
	_base_modulate = modulate
	focus_mode = Control.FOCUS_ALL
	mouse_entered.connect(_on_mouse_entered)
	mouse_exited.connect(_on_mouse_exited)
	pressed.connect(_on_pressed)
	resized.connect(_on_resized)
	_apply_variant()

func _gui_input(event: InputEvent) -> void:
	if event is InputEventScreenTouch or event is InputEventMouseButton:
		var pressed_state: bool = false
		if event is InputEventScreenTouch:
			pressed_state = event.pressed
		else:
			pressed_state = event.pressed and event.button_index == MOUSE_BUTTON_LEFT
		_animate_press(pressed_state)

func _on_resized() -> void:
	pivot_offset = size * 0.5

func _on_pressed() -> void:
	if AudioManager != null:
		AudioManager.play_sfx("click", -5.0)
	_animate_press(false)

func _on_mouse_entered() -> void:
	if AudioManager != null:
		AudioManager.play_sfx("hover", -12.0)
	_start_hover_shimmer()

func _on_mouse_exited() -> void:
	_stop_hover_shimmer()

func _animate_press(pressed: bool) -> void:
	if _press_tween != null:
		_press_tween.kill()
	_press_tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BACK)
	var target_scale: Vector2 = Vector2.ONE
	if pressed:
		target_scale = Vector2(0.95, 0.95)
	_press_tween.tween_property(self, "scale", target_scale, 0.08)

func _start_hover_shimmer() -> void:
	if _hover_tween != null:
		_hover_tween.kill()
	_hover_tween = create_tween().set_loops()
	_hover_tween.tween_property(self, "modulate", _base_modulate.lightened(0.1), 0.25)
	_hover_tween.tween_property(self, "modulate", _base_modulate, 0.25)

func _stop_hover_shimmer() -> void:
	if _hover_tween != null:
		_hover_tween.kill()
	modulate = _base_modulate

func _apply_variant() -> void:
	if use_rpg_assets:
		_apply_rpg_variant()
		return

	var button_color: Color = Color("d4af37")
	var border_color: Color = Color("4a4540")
	match button_type:
		ButtonType.PRIMARY:
			button_color = Color("d4af37")
		ButtonType.SECONDARY:
			button_color = Color("2d2d2d")
		ButtonType.DANGER:
			button_color = Color("b03030")
			border_color = Color("7d1e1e")

	var normal: StyleBoxFlat = _build_style(button_color.darkened(0.35), border_color)
	var hover: StyleBoxFlat = _build_style(button_color.darkened(0.2), button_color.lightened(0.15))
	var pressed: StyleBoxFlat = _build_style(button_color.darkened(0.5), border_color)

	add_theme_stylebox_override("normal", normal)
	add_theme_stylebox_override("hover", hover)
	add_theme_stylebox_override("pressed", pressed)
	add_theme_stylebox_override("focus", hover)
	add_theme_stylebox_override("disabled", pressed)
	add_theme_color_override("font_color", Color("f5f5dc"))
	add_theme_color_override("font_hover_color", Color("f5f5dc"))
	add_theme_color_override("font_pressed_color", Color("f5f5dc"))

func _apply_rpg_variant() -> void:
	var normal_rect: Rect2 = Rect2(224, 32, 64, 16)
	var hover_rect: Rect2 = Rect2(224, 64, 64, 16)
	var pressed_rect: Rect2 = Rect2(224, 96, 64, 16)

	match button_type:
		ButtonType.SECONDARY:
			normal_rect = Rect2(296, 32, 64, 16)
			hover_rect = Rect2(296, 64, 64, 16)
			pressed_rect = Rect2(296, 96, 64, 16)
		ButtonType.DANGER:
			normal_rect = Rect2(224, 432, 64, 16)
			hover_rect = Rect2(224, 416, 64, 16)
			pressed_rect = Rect2(224, 448, 64, 16)

	var normal: StyleBoxTexture = _build_rpg_style(normal_rect)
	var hover: StyleBoxTexture = _build_rpg_style(hover_rect)
	var pressed: StyleBoxTexture = _build_rpg_style(pressed_rect)

	add_theme_stylebox_override("normal", normal)
	add_theme_stylebox_override("hover", hover)
	add_theme_stylebox_override("pressed", pressed)
	add_theme_stylebox_override("focus", hover)
	add_theme_stylebox_override("disabled", normal)

	add_theme_color_override("font_color", Color("0b0f0f"))
	add_theme_color_override("font_hover_color", Color("0b0f0f"))
	add_theme_color_override("font_pressed_color", Color("0b0f0f"))
	add_theme_color_override("font_disabled_color", Color("0b0f0f"))

func _build_rpg_style(region: Rect2) -> StyleBoxTexture:
	var sb: StyleBoxTexture = StyleBoxTexture.new()
	sb.texture = RPG_BUTTONS_TEXTURE
	sb.region_rect = region
	sb.texture_margin_left = 6
	sb.texture_margin_top = 6
	sb.texture_margin_right = 6
	sb.texture_margin_bottom = 6
	sb.content_margin_left = 10
	sb.content_margin_top = 6
	sb.content_margin_right = 10
	sb.content_margin_bottom = 6
	return sb

func _build_style(bg: Color, border: Color) -> StyleBoxFlat:
	var sb: StyleBoxFlat = StyleBoxFlat.new()
	sb.bg_color = bg
	sb.border_color = border
	sb.border_width_left = 2
	sb.border_width_top = 2
	sb.border_width_right = 2
	sb.border_width_bottom = 2
	sb.corner_radius_top_left = 0
	sb.corner_radius_top_right = 0
	sb.corner_radius_bottom_left = 0
	sb.corner_radius_bottom_right = 0
	sb.content_margin_left = 10
	sb.content_margin_right = 10
	sb.content_margin_top = 8
	sb.content_margin_bottom = 8
	return sb
