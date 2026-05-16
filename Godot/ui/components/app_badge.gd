extends Label
class_name AppBadge

enum BadgeVariant {
	NEUTRAL,
	SUCCESS,
	WARNING,
	DANGER,
	INFO,
	XP,
	COINS,
}

const FONT: Font = preload("res://assets/fonts/inter_medium.tres")

@export var variant: BadgeVariant = BadgeVariant.NEUTRAL:
	set(value):
		variant = value
		if is_node_ready():
			_apply_variant()

@export var badge_text: String = "":
	set(value):
		badge_text = value
		if is_node_ready():
			_update_text()

@export var mobile_mode: bool = false:
	set(value):
		mobile_mode = value
		if is_node_ready():
			_apply_variant()

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	_update_text()
	_apply_variant()

func set_badge(value: String) -> void:
	badge_text = value
	text = value.to_upper()

func _update_text() -> void:
	text = badge_text.to_upper()

func _apply_variant() -> void:
	var bg: Color
	var border: Color
	var text_color: Color

	match variant:
		BadgeVariant.NEUTRAL:
			bg = Color(1.0, 247.0 / 255.0, 237.0 / 255.0, 0.08)
			border = Color(1.0, 1.0, 1.0, 0.06)
			text_color = Color("eadac2")
		BadgeVariant.SUCCESS:
			bg = Color(110.0 / 255.0, 231.0 / 255.0, 183.0 / 255.0, 0.14)
			border = Color(167.0 / 255.0, 243.0 / 255.0, 208.0 / 255.0, 0.25)
			text_color = Color("ecfdf5")
		BadgeVariant.WARNING:
			bg = Color(252.0 / 255.0, 211.0 / 255.0, 77.0 / 255.0, 0.12)
			border = Color(253.0 / 255.0, 230.0 / 255.0, 138.0 / 255.0, 0.25)
			text_color = Color("fefce8")
		BadgeVariant.DANGER:
			bg = Color(251.0 / 255.0, 113.0 / 255.0, 133.0 / 255.0, 0.12)
			border = Color(253.0 / 255.0, 164.0 / 255.0, 175.0 / 255.0, 0.25)
			text_color = Color("fff1f2")
		BadgeVariant.INFO:
			bg = Color(56.0 / 255.0, 189.0 / 255.0, 248.0 / 255.0, 0.12)
			border = Color(125.0 / 255.0, 211.0 / 255.0, 252.0 / 255.0, 0.25)
			text_color = Color("f0f9ff")
		BadgeVariant.XP:
			bg = Color(212.0 / 255.0, 175.0 / 255.0, 55.0 / 255.0, 0.15)
			border = Color(212.0 / 255.0, 175.0 / 255.0, 55.0 / 255.0, 0.25)
			text_color = Color("fef3c7")
		BadgeVariant.COINS:
			bg = Color(245.0 / 255.0, 158.0 / 255.0, 11.0 / 255.0, 0.15)
			border = Color(245.0 / 255.0, 158.0 / 255.0, 11.0 / 255.0, 0.25)
			text_color = Color("fffbeb")

	var pad_v: float = 4.0
	var pad_h: float = 12.0
	var font_size: int = 11

	if mobile_mode:
		pad_v = 3.0
		pad_h = 8.0
		font_size = 9

	var style := StyleBoxFlat.new()
	style.bg_color = bg
	style.border_color = border
	style.border_width_left = 1
	style.border_width_top = 1
	style.border_width_right = 1
	style.border_width_bottom = 1
	style.corner_radius_top_left = 14.0
	style.corner_radius_top_right = 14.0
	style.corner_radius_bottom_left = 14.0
	style.corner_radius_bottom_right = 14.0
	style.content_margin_left = pad_h
	style.content_margin_right = pad_h
	style.content_margin_top = pad_v
	style.content_margin_bottom = pad_v

	add_theme_stylebox_override("normal", style)
	add_theme_color_override("font_color", text_color)
	add_theme_font_override("font", FONT)
	add_theme_font_size_override("font_size", font_size)
