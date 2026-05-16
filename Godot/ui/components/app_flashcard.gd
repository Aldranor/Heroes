extends Control
class_name AppFlashcard

signal flipped

const FONT_BODY: Font = preload("res://assets/fonts/inter_regular.tres")

@export var question_text: String = "":
	set(v):
		question_text = v
		if is_node_ready(): _front_label.text = v

@export var answer_text: String = "":
	set(v):
		answer_text = v
		if is_node_ready(): _back_label.text = v

@export var is_flipped: bool = false:
	set(v):
		is_flipped = v
		if is_node_ready(): _apply_flip()

@onready var _front_panel: PanelContainer = %FrontPanel
@onready var _back_panel: PanelContainer = %BackPanel
@onready var _front_label: Label = %FrontLabel
@onready var _back_label: Label = %BackLabel
@onready var _front_hint: Label = %FrontHint
@onready var _back_hint: Label = %BackHint

func _ready() -> void:
	gui_input.connect(_on_tap)
	_style()
	_front_label.text = question_text
	_back_label.text = answer_text
	_apply_flip()

func _style() -> void:
	_panel_style(_front_panel)
	_panel_style(_back_panel)
	for l in [_front_label, _back_label]:
		l.add_theme_font_override("font", FONT_BODY)
		l.add_theme_font_size_override("font_size", 16)
		l.add_theme_color_override("font_color", Color("f5efe7"))
		l.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	for h in [_front_hint, _back_hint]:
		h.add_theme_font_size_override("font_size", 10)
		h.add_theme_color_override("font_color", Color("a8a29e"))
		h.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER

func _panel_style(p: PanelContainer) -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color(37.0 / 255.0, 31.0 / 255.0, 28.0 / 255.0, 0.94)
	s.border_color = Color(201.0 / 255.0, 168.0 / 255.0, 122.0 / 255.0, 0.24)
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 24; s.corner_radius_top_right = 24
	s.corner_radius_bottom_left = 24; s.corner_radius_bottom_right = 24
	s.content_margin_left = 24; s.content_margin_right = 24
	s.content_margin_top = 24; s.content_margin_bottom = 24
	s.shadow_size = 30; s.shadow_color = Color(0, 0, 0, 0.18)
	s.shadow_offset = Vector2(0, 16)
	p.add_theme_stylebox_override("panel", s)

func _apply_flip() -> void:
	_front_panel.visible = not is_flipped
	_back_panel.visible = is_flipped

func _on_tap(event: InputEvent) -> void:
	if (event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT) or (event is InputEventScreenTouch and event.pressed):
		is_flipped = not is_flipped
		flipped.emit()

func setup(question: String, answer: String) -> void:
	question_text = question
	answer_text = answer
	is_flipped = false
