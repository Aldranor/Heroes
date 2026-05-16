extends PanelContainer
class_name AppStepPanel


const FONT_DISPLAY: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_INTER: Font = preload("res://assets/fonts/inter_medium.tres")

@export var step_index: int = 1:
	set(value):
		step_index = value
		if is_node_ready():
			_update_text()

@export var eyebrow_text: String = "":
	set(value):
		eyebrow_text = value
		if is_node_ready():
			_eyebrow_label.text = value.to_upper()

@export var title_text: String = "":
	set(value):
		title_text = value
		if is_node_ready():
			_title_label.text = value

@export var description_text: String = "":
	set(value):
		description_text = value
		if is_node_ready():
			_description_label.text = value

@export var is_active: bool = true:
	set(value):
		is_active = value
		visible = value
		if is_active and is_node_ready():
			_play_enter_animation()

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel
@onready var _content_box: VBoxContainer = %ContentBox

func _ready() -> void:
	_style_panel()
	_style_labels()
	_update_text()
	if is_active:
		_play_enter_animation()
	else:
		visible = false

func get_content_container() -> VBoxContainer:
	return _content_box

func _style_panel() -> void:
	var style := StyleBoxFlat.new()
	style.bg_color = Color.WHITE
	style.border_color = Color("e7e5e4")
	style.border_width_left = 1
	style.border_width_top = 1
	style.border_width_right = 1
	style.border_width_bottom = 1
	style.corner_radius_top_left = 32.0
	style.corner_radius_top_right = 32.0
	style.corner_radius_bottom_left = 32.0
	style.corner_radius_bottom_right = 32.0
	style.content_margin_left = 24
	style.content_margin_right = 24
	style.content_margin_top = 24
	style.content_margin_bottom = 24
	style.shadow_size = 40
	style.shadow_color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.08)
	style.shadow_offset = Vector2(0, 20)
	add_theme_stylebox_override("panel", style)

func _style_labels() -> void:
	_eyebrow_label.add_theme_font_override("font", FONT_INTER)
	_eyebrow_label.add_theme_font_size_override("font_size", 10)
	_eyebrow_label.add_theme_color_override("font_color", Color("78716c"))

	_title_label.add_theme_font_override("font", FONT_DISPLAY)
	_title_label.add_theme_font_size_override("font_size", 24)
	_title_label.add_theme_color_override("font_color", Color("1c1917"))

	_description_label.add_theme_font_override("font", FONT_INTER)
	_description_label.add_theme_font_size_override("font_size", 14)
	_description_label.add_theme_color_override("font_color", Color("57534e"))
	_description_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART

func _update_text() -> void:
	_eyebrow_label.text = eyebrow_text.to_upper()
	_title_label.text = title_text
	_description_label.text = description_text

func _play_enter_animation() -> void:
	modulate.a = 0.0
	position += Vector2(0, 10)
	var tween := create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_QUINT)
	tween.tween_property(self, "modulate:a", 1.0, 0.24)
	tween.parallel().tween_property(self, "position", position - Vector2(0, 10), 0.24)
