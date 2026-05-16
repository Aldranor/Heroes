extends Control
class_name StatBar

enum BarType {
	HP,
	MP,
	XP
}

@export var bar_type: BarType = BarType.HP:
	set(value):
		bar_type = value
		_apply_bar_style()

@export var max_value: int = 100:
	set(value):
		max_value = max(1, value)
		if is_node_ready():
			_progress.max_value = max_value
		_update_label()

@export var current_value: int = 100:
	set(value):
		current_value = clampi(value, 0, max_value)
		_animate_value_change(current_value)

@onready var _progress: ProgressBar = %ProgressBar
@onready var _value_label: Label = %ValueLabel

var _value_tween: Tween

func _ready() -> void:
	custom_minimum_size = Vector2(140, 24)
	_progress.min_value = 0
	_progress.max_value = max_value
	_progress.value = current_value
	_apply_bar_style()
	_update_label()

## Met à jour les valeurs affichées.
func set_values(current: int, maximum: int) -> void:
	max_value = max(1, maximum)
	current_value = clampi(current, 0, max_value)
	_animate_value_change(current_value)

func _animate_value_change(target: int) -> void:
	if not is_node_ready():
		return
	if _value_tween != null:
		_value_tween.kill()
	_value_tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_CUBIC)
	_value_tween.tween_property(_progress, "value", target, 0.2)
	_update_label()

func _update_label() -> void:
	if not is_node_ready():
		return
	_value_label.text = "%d / %d" % [current_value, max_value]

func _apply_bar_style() -> void:
	if not is_node_ready():
		return
	var color: Color = Color("b03030")
	match bar_type:
		BarType.HP:
			color = Color("b03030")
		BarType.MP:
			color = Color("3f6ed8")
		BarType.XP:
			color = Color("d4af37")

	var fill: StyleBoxFlat = StyleBoxFlat.new()
	fill.bg_color = color
	fill.corner_radius_top_left = 0
	fill.corner_radius_top_right = 0
	fill.corner_radius_bottom_left = 0
	fill.corner_radius_bottom_right = 0
	_progress.add_theme_stylebox_override("fill", fill)
