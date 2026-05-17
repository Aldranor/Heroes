extends VBoxContainer
class_name AppAuthHeader

const UI_MEDIUM_FONT: Font = preload("res://assets/fonts/inter_medium.tres")

@export var header_title: String = "":
	set(value):
		header_title = value
		if is_node_ready():
			_title_label.text = value

@export var header_description: String = "":
	set(value):
		header_description = value
		if is_node_ready():
			_description_label.text = value

@export var title_variation: StringName = &"LabelAuthTitle":
	set(value):
		title_variation = value
		if is_node_ready():
			_title_label.theme_type_variation = title_variation

@export var description_variation: StringName = &"LabelAuthDescription":
	set(value):
		description_variation = value
		if is_node_ready():
			_description_label.theme_type_variation = description_variation

@export var title_font_size: int = 20:
	set(value):
		title_font_size = max(value, 12)
		if is_node_ready():
			_title_label.add_theme_font_size_override("font_size", title_font_size)

@export var title_use_medium_font: bool = true:
	set(value):
		title_use_medium_font = value
		if is_node_ready():
			if title_use_medium_font:
				_title_label.add_theme_font_override("font", UI_MEDIUM_FONT)
			else:
				_title_label.remove_theme_font_override("font")

@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel

func _ready() -> void:
	_apply_style()
	_title_label.text = header_title
	_description_label.text = header_description

func _apply_style() -> void:
	add_theme_constant_override("separation", 8)
	_title_label.theme_type_variation = title_variation
	_title_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_title_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	_title_label.add_theme_font_size_override("font_size", title_font_size)
	if title_use_medium_font:
		_title_label.add_theme_font_override("font", UI_MEDIUM_FONT)
	else:
		_title_label.remove_theme_font_override("font")

	_description_label.theme_type_variation = description_variation
	_description_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_description_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
