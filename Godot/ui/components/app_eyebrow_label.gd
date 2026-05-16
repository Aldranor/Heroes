extends Label
class_name AppEyebrowLabel

const AcademyTypography = preload("res://themes/typography.gd")

const _MEDIUM_FONT: Font = preload("res://assets/fonts/inter_medium.tres")

func _ready() -> void:
	_apply_style()

func set_eyebrow_text(value: String) -> void:
	text = value.to_upper()
	_apply_style()

func _apply_style() -> void:
	if not is_node_ready():
		return
	text = text.to_upper()
	add_theme_font_override("font", _MEDIUM_FONT)
	add_theme_font_size_override("font_size", AcademyTypography.EYEBROW)
	remove_theme_color_override("font_color")
	add_theme_constant_override("line_spacing", 1)
	add_theme_constant_override("outline_size", 0)
	add_theme_constant_override("shadow_offset_x", 0)
	add_theme_constant_override("shadow_offset_y", 0)
	add_theme_constant_override("shadow_outline_size", 0)
	add_theme_constant_override("font_spacing", 2)
