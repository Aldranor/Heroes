extends Control
class_name AppInventorySlot

signal slot_pressed(slot_index: int)

const FONT: Font = preload("res://assets/fonts/inter_medium.tres")

@export var slot_index: int = 0
@export var item_icon: Texture2D:
	set(v):
		item_icon = v
		if is_node_ready(): _icon_rect.texture = v
@export var item_count: int = 0:
	set(v):
		item_count = v
		if is_node_ready(): _refresh_count()
@export var item_label: String = "":
	set(v):
		item_label = v
		if is_node_ready(): _name_label.text = v
@export var is_selected: bool = false:
	set(v):
		is_selected = v
		if is_node_ready(): _apply_selection()
@export var is_empty: bool = true:
	set(v):
		is_empty = v
		if is_node_ready(): _refresh_empty()

@onready var _icon_rect: TextureRect = %IconRect
@onready var _count_badge: PanelContainer = %CountBadge
@onready var _count_label: Label = %CountLabel
@onready var _name_label: Label = %NameLabel

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	gui_input.connect(_on_tap)
	_style()
	if item_icon: _icon_rect.texture = item_icon
	_refresh_count()
	_name_label.text = item_label
	_refresh_empty()
	_apply_selection()

func _style() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color(1.0, 1.0, 1.0, 0.05)
	s.border_color = Color(1.0, 1.0, 1.0, 0.06)
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 16; s.corner_radius_top_right = 16
	s.corner_radius_bottom_left = 16; s.corner_radius_bottom_right = 16
	_count_badge.add_theme_stylebox_override("panel", s)

	_icon_rect.expand_mode = TextureRect.EXPAND_FIT_WIDTH_PROPORTIONAL
	_icon_rect.stretch_mode = TextureRect.STRETCH_KEEP_ASPECT_CENTERED

	_count_label.add_theme_font_override("font", FONT)
	_count_label.add_theme_font_size_override("font_size", 10)
	_count_label.add_theme_color_override("font_color", Color("fde68a"))

	_name_label.add_theme_font_override("font", FONT)
	_name_label.add_theme_font_size_override("font_size", 10)
	_name_label.add_theme_color_override("font_color", Color("a8a29e"))
	_name_label.clip_text = true

func _refresh_count() -> void:
	_count_label.text = str(item_count)
	_count_badge.visible = item_count > 0

func _refresh_empty() -> void:
	if is_empty:
		_icon_rect.modulate = Color(1, 1, 1, 0.2)
		_count_badge.modulate = Color(1, 1, 1, 0.3)
	else:
		_icon_rect.modulate = Color(1, 1, 1, 1)
		_count_badge.modulate = Color(1, 1, 1, 1)

func _apply_selection() -> void:
	var s := StyleBoxFlat.new()
	if is_selected:
		s.bg_color = Color(245.0 / 255.0, 198.0 / 255.0, 100.0 / 255.0, 0.15)
		s.border_color = Color("fde68a")
	else:
		s.bg_color = Color(1.0, 1.0, 1.0, 0.04)
		s.border_color = Color(1.0, 1.0, 1.0, 0.08)
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 20; s.corner_radius_top_right = 20
	s.corner_radius_bottom_left = 20; s.corner_radius_bottom_right = 20
	s.content_margin_left = 8; s.content_margin_right = 8
	s.content_margin_top = 8; s.content_margin_bottom = 4
	add_theme_stylebox_override("panel", s)

func _on_tap(event: InputEvent) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		slot_pressed.emit(slot_index)

func setup(idx: int, icon: Texture2D, label: String, count: int) -> void:
	slot_index = idx
	item_icon = icon
	item_label = label
	item_count = count
	is_empty = icon == null
