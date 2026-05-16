extends Label
class_name AppHeading

const AcademyTypography = preload("res://themes/typography.gd")

@export_range(1, 3, 1) var level: int = 1:
	set(value):
		level = clampi(value, 1, 3)
		_apply_style()

@export var heading_color: Color = Color(1, 1, 1, 1):
	set(value):
		heading_color = value
		_apply_style()

const _SERIF_FONT: Font = preload("res://assets/fonts/cormorant_semibold.tres")

func _ready() -> void:
	_apply_style()

func _apply_style() -> void:
	if not is_node_ready():
		return
	var font_size: int = AcademyTypography.H1_SIZE
	match level:
		1:
			font_size = AcademyTypography.H1_SIZE
		2:
			font_size = AcademyTypography.H2_SIZE
		3:
			font_size = AcademyTypography.H3_SIZE
	add_theme_font_override("font", _SERIF_FONT)
	add_theme_font_size_override("font_size", font_size)
	if heading_color != Color(1, 1, 1, 1):
		add_theme_color_override("font_color", heading_color)
	else:
		remove_theme_color_override("font_color")
	add_theme_constant_override("line_spacing", 2)
	autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
