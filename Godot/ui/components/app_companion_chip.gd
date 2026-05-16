extends PanelContainer
class_name AppCompanionChip

const FONT_NAME: Font = preload("res://assets/fonts/inter_medium.tres")
const FONT_DESC: Font = preload("res://assets/fonts/inter_regular.tres")

@export var companion_name: String = "":
	set(value):
		companion_name = value
		if is_node_ready():
			_name_label.text = value

@export var companion_description: String = "":
	set(value):
		companion_description = value
		if is_node_ready():
			_description_label.text = value

@export var is_active_chip: bool = false:
	set(value):
		is_active_chip = value
		if is_node_ready():
			_apply_state()

@onready var _icon_container: PanelContainer = %IconContainer
@onready var _name_label: Label = %NameLabel
@onready var _description_label: Label = %DescriptionLabel

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	_name_label.text = companion_name
	_description_label.text = companion_description
	_style_base_labels()
	_apply_state()

func set_icon(icon: Texture2D) -> void:
	var rect := TextureRect.new()
	rect.texture = icon
	rect.expand_mode = TextureRect.EXPAND_IGNORE_SIZE
	rect.stretch_mode = TextureRect.STRETCH_KEEP_ASPECT_CENTERED
	rect.custom_minimum_size = Vector2(28, 28)
	for c in _icon_container.get_children():
		c.queue_free()
	_icon_container.add_child(rect)

func set_icon_text(emoji: String) -> void:
	for c in _icon_container.get_children():
		c.queue_free()
	var label := Label.new()
	label.text = emoji
	label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	label.add_theme_font_size_override("font_size", 22)
	_icon_container.add_child(label)

func _style_base_labels() -> void:
	_name_label.add_theme_font_override("font", FONT_NAME)
	_name_label.add_theme_font_size_override("font_size", 14)
	_name_label.add_theme_color_override("font_color", Color("1c1917"))
	_description_label.add_theme_font_override("font", FONT_DESC)
	_description_label.add_theme_font_size_override("font_size", 12)
	_description_label.add_theme_color_override("font_color", Color("57534e"))

func _apply_state() -> void:
	var style := StyleBoxFlat.new()
	style.bg_color = Color.WHITE
	style.corner_radius_top_left = 20.0
	style.corner_radius_top_right = 20.0
	style.corner_radius_bottom_left = 20.0
	style.corner_radius_bottom_right = 20.0
	style.content_margin_left = 16
	style.content_margin_right = 16
	style.content_margin_top = 14
	style.content_margin_bottom = 14
	style.border_width_left = 1
	style.border_width_top = 1
	style.border_width_right = 1
	style.border_width_bottom = 1

	if is_active_chip:
		style.border_color = Color(17.0 / 255.0, 24.0 / 255.0, 39.0 / 255.0, 0.85)
		style.shadow_size = 24
		style.shadow_color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.08)
		style.shadow_offset = Vector2(0, 12)
	else:
		style.border_color = Color(120.0 / 255.0, 113.0 / 255.0, 108.0 / 255.0, 0.18)
		style.shadow_size = 0

	add_theme_stylebox_override("panel", style)
