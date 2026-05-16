extends ProgressBar
class_name AppProgressBar

enum BarVariant {
	NEUTRAL,
	SUCCESS,
	DANGER,
	XP,
}

@export var bar_variant: BarVariant = BarVariant.NEUTRAL:
	set(value):
		bar_variant = value
		if is_node_ready():
			_apply_style()

@export var height_px: float = 8.0:
	set(value):
		height_px = value
		if is_node_ready():
			_apply_style()

func _ready() -> void:
	value = 0
	min_value = 0
	max_value = 100
	show_percentage = false
	_apply_style()

func set_progress_pct(pct: float) -> void:
	value = clampf(pct, 0.0, 100.0)

func _apply_style() -> void:
	var fill_color: Color
	match bar_variant:
		BarVariant.SUCCESS:
			fill_color = Color(110.0 / 255.0, 231.0 / 255.0, 183.0 / 255.0, 1.0)
		BarVariant.DANGER:
			fill_color = Color(251.0 / 255.0, 113.0 / 255.0, 133.0 / 255.0, 1.0)
		BarVariant.XP:
			fill_color = Color(212.0 / 255.0, 175.0 / 255.0, 55.0 / 255.0, 1.0)
		_:
			fill_color = Color("fde68a")

	var track := StyleBoxFlat.new()
	track.bg_color = Color(1.0, 1.0, 1.0, 0.08)
	track.corner_radius_top_left = 999.0
	track.corner_radius_top_right = 999.0
	track.corner_radius_bottom_left = 999.0
	track.corner_radius_bottom_right = 999.0

	var fill := StyleBoxFlat.new()
	fill.bg_color = fill_color
	fill.corner_radius_top_left = 999.0
	fill.corner_radius_top_right = 999.0
	fill.corner_radius_bottom_left = 999.0
	fill.corner_radius_bottom_right = 999.0

	add_theme_stylebox_override("background", track)
	add_theme_stylebox_override("fill", fill)
	custom_minimum_size = Vector2(0, height_px)
