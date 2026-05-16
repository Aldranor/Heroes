extends PanelContainer
class_name AppScreenHeader

signal back_pressed

const FONT_TITLE: Font = preload("res://assets/fonts/inter_medium.tres")

@export var screen_title: String = "":
	set(value):
		screen_title = value
		if is_node_ready():
			_title_label.text = value

@export var show_back_button: bool = true:
	set(value):
		show_back_button = value
		if is_node_ready():
			_back_button.visible = value

@onready var _back_button: Button = %BackButton
@onready var _title_label: Label = %TitleLabel

func _ready() -> void:
	_style_panel()
	_style_title()
	_back_button.pressed.connect(_on_back_pressed)
	_back_button.visible = show_back_button
	_title_label.text = screen_title

func _style_panel() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color(23.0 / 255.0, 20.0 / 255.0, 18.0 / 255.0, 0.95)
	s.border_color = Color(245.0 / 255.0, 158.0 / 255.0, 11.0 / 255.0, 0.1)
	s.border_width_bottom = 1
	s.content_margin_left = 16
	s.content_margin_right = 16
	s.content_margin_top = 8
	s.content_margin_bottom = 8
	s.corner_radius_top_left = 16
	s.corner_radius_top_right = 16
	add_theme_stylebox_override("panel", s)

func _style_title() -> void:
	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 16)
	_title_label.add_theme_color_override("font_color", Color("f5efe7"))

func _on_back_pressed() -> void:
	back_pressed.emit()
