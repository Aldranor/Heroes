extends PanelContainer
class_name AppQuestCard

signal quest_pressed(quest_key: String)

const FONT_TITLE: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_DESC: Font = preload("res://assets/fonts/inter_regular.tres")

@export var quest_key: String = "":
	set(v): quest_key = v

@export var quest_title: String = "":
	set(v):
		quest_title = v
		if is_node_ready(): _title_label.text = v

@export var quest_description: String = "":
	set(v):
		quest_description = v
		if is_node_ready(): _desc_label.text = v

@export var status_text: String = "":
	set(v):
		status_text = v
		if is_node_ready(): _status_badge.text = v

@export var tone_color: Color = Color("f59e0b"):
	set(v):
		tone_color = v
		if is_node_ready(): _apply_tone()

@export var cta_text: String = "":
	set(v):
		cta_text = v
		if is_node_ready(): _cta_button.text = v

@export var is_available: bool = true:
	set(v):
		is_available = v
		if is_node_ready(): _cta_button.visible = v

@export var image_texture: Texture2D:
	set(v):
		image_texture = v
		if is_node_ready(): _image_rect.texture = v

@onready var _image_rect: TextureRect = %ImageRect
@onready var _status_badge: Label = %StatusBadge
@onready var _title_label: Label = %TitleLabel
@onready var _desc_label: Label = %DescriptionLabel
@onready var _cta_button: Button = %CtaButton

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	gui_input.connect(_on_gui)
	_style_panel()
	_style_labels()
	if _title_label != null:
		_title_label.text = quest_title
	if _desc_label != null:
		_desc_label.text = quest_description
	if _status_badge != null:
		_status_badge.text = status_text
	if _cta_button != null:
		_cta_button.text = cta_text
		_cta_button.visible = is_available
		_cta_button.pressed.connect(_on_cta)
	if image_texture and _image_rect != null:
		_image_rect.texture = image_texture
	_apply_tone()

func _style_panel() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color(43.0 / 255.0, 36.0 / 255.0, 31.0 / 255.0, 0.96)
	s.border_color = Color(196.0 / 255.0, 164.0 / 255.0, 122.0 / 255.0, 0.18)
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 28; s.corner_radius_top_right = 28
	s.corner_radius_bottom_left = 28; s.corner_radius_bottom_right = 28
	s.shadow_size = 34; s.shadow_color = Color(0, 0, 0, 0.22)
	s.shadow_offset = Vector2(0, 20)
	s.content_margin_bottom = 16
	add_theme_stylebox_override("panel", s)

func _style_labels() -> void:
	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 22)
	_title_label.add_theme_color_override("font_color", Color("f5f5f4"))
	_desc_label.add_theme_font_override("font", FONT_DESC)
	_desc_label.add_theme_font_size_override("font_size", 13)
	_desc_label.add_theme_color_override("font_color", Color("d6d3d1"))
	_desc_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART

func _apply_tone() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = tone_color.darkened(0.4)
	s.border_color = tone_color
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 999; s.corner_radius_top_right = 999
	s.corner_radius_bottom_left = 999; s.corner_radius_bottom_right = 999
	_status_badge.add_theme_stylebox_override("normal", s)
	_status_badge.add_theme_color_override("font_color", tone_color.lightened(0.4))
	_style_cta(tone_color)

func _style_cta(tone: Color) -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = tone
	s.corner_radius_top_left = 999; s.corner_radius_top_right = 999
	s.corner_radius_bottom_left = 999; s.corner_radius_bottom_right = 999
	s.content_margin_left = 16; s.content_margin_right = 16
	s.content_margin_top = 8; s.content_margin_bottom = 8
	_cta_button.add_theme_stylebox_override("normal", s)
	_cta_button.add_theme_stylebox_override("hover", s)
	_cta_button.add_theme_color_override("font_color", Color("1c1917"))
	_cta_button.add_theme_font_size_override("font_size", 11)

func _on_gui(event: InputEvent) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		quest_pressed.emit(quest_key)

func _on_cta() -> void:
	quest_pressed.emit(quest_key)
