extends Button
class_name AppPillButton

enum PillVariant {
	PRIMARY,
	SECONDARY,
	MUTED,
}

const FONT: Font = preload("res://assets/fonts/inter_medium.tres")

@export var pill_variant: PillVariant = PillVariant.PRIMARY:
	set(value):
		pill_variant = value
		if is_node_ready():
			_apply_variant()

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	_apply_variant()

func _apply_variant() -> void:
	var style := StyleBoxFlat.new()
	var font_color: Color

	match pill_variant:
		PillVariant.SECONDARY:
			style.bg_color = Color(1.0, 1.0, 1.0, 0.05)
			style.border_color = Color(1.0, 1.0, 1.0, 0.1)
			style.border_width_left = 1
			style.border_width_top = 1
			style.border_width_right = 1
			style.border_width_bottom = 1
			font_color = Color("f5f5f4")
		PillVariant.MUTED:
			style.bg_color = Color.TRANSPARENT
			style.border_color = Color(1.0, 1.0, 1.0, 0.08)
			style.border_width_left = 1
			style.border_width_top = 1
			style.border_width_right = 1
			style.border_width_bottom = 1
			font_color = Color("a8a29e")
		_:
			style.bg_color = Color("fde68a")
			font_color = Color("0c0a09")

	style.corner_radius_top_left = 999.0
	style.corner_radius_top_right = 999.0
	style.corner_radius_bottom_left = 999.0
	style.corner_radius_bottom_right = 999.0
	style.content_margin_left = 12
	style.content_margin_right = 12
	style.content_margin_top = 6
	style.content_margin_bottom = 6

	add_theme_stylebox_override("normal", style)
	add_theme_color_override("font_color", font_color)
	add_theme_font_override("font", FONT)
	add_theme_font_size_override("font_size", 11)
