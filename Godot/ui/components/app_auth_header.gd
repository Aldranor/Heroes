extends Control
class_name AppAuthHeader

const FONT_TITLE: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_BODY: Font = preload("res://assets/fonts/inter_medium.tres")

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

@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel

func _ready() -> void:
	_style_labels()
	_title_label.text = header_title
	_description_label.text = header_description

func _style_labels() -> void:
	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 28)
	_title_label.add_theme_color_override("font_color", Color.WHITE)
	_title_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER

	_description_label.add_theme_font_override("font", FONT_BODY)
	_description_label.add_theme_font_size_override("font_size", 14)
	_description_label.add_theme_color_override("font_color", Color("a8a29e"))
	_description_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_description_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
