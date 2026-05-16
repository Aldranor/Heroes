extends Control
class_name AppChoiceDialog

signal choice_selected(index: int)

const FONT_TITLE: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_BODY: Font = preload("res://assets/fonts/inter_regular.tres")

@export var dialog_title: String = "":
	set(v):
		dialog_title = v
		if is_node_ready(): _title_label.text = v

@export var dialog_text: String = "":
	set(v):
		dialog_text = v
		if is_node_ready(): _body_label.text = v

@export var choices: Array[String] = []:
	set(v):
		choices = v
		if is_node_ready(): _rebuild_choices()

@export var selected_index: int = -1

@onready var _overlay: ColorRect = %Overlay
@onready var _card: PanelContainer = %Card
@onready var _title_label: Label = %TitleLabel
@onready var _body_label: Label = %BodyLabel
@onready var _choices_container: VBoxContainer = %ChoicesContainer

var _choice_buttons: Array[Button] = []

func _ready() -> void:
	mouse_filter = Control.MOUSE_FILTER_PASS
	_style()
	_title_label.text = dialog_title
	_body_label.text = dialog_text
	_rebuild_choices()
	_overlay.gui_input.connect(_on_overlay)

func _style() -> void:
	var card_s := StyleBoxFlat.new()
	card_s.bg_color = Color(37.0 / 255.0, 31.0 / 255.0, 28.0 / 255.0, 0.98)
	card_s.border_color = Color(214.0 / 255.0, 197.0 / 255.0, 168.0 / 255.0, 0.14)
	card_s.border_width_left = 1; card_s.border_width_top = 1
	card_s.border_width_right = 1; card_s.border_width_bottom = 1
	card_s.corner_radius_top_left = 28; card_s.corner_radius_top_right = 28
	card_s.corner_radius_bottom_left = 28; card_s.corner_radius_bottom_right = 28
	card_s.content_margin_left = 20; card_s.content_margin_right = 20
	card_s.content_margin_top = 20; card_s.content_margin_bottom = 20
	card_s.shadow_size = 36; card_s.shadow_color = Color(0, 0, 0, 0.22)
	card_s.shadow_offset = Vector2(0, 18)
	_card.add_theme_stylebox_override("panel", card_s)

	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 22)
	_title_label.add_theme_color_override("font_color", Color("f5efe7"))
	_body_label.add_theme_font_override("font", FONT_BODY)
	_body_label.add_theme_font_size_override("font_size", 14)
	_body_label.add_theme_color_override("font_color", Color("a8a29e"))
	_body_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART

func _rebuild_choices() -> void:
	for b in _choice_buttons:
		b.queue_free()
	_choice_buttons.clear()

	for i in choices.size():
		var btn := Button.new()
		btn.text = choices[i]
		btn.size_flags_horizontal = Control.SIZE_FILL
		btn.alignment = HORIZONTAL_ALIGNMENT_CENTER
		btn.pressed.connect(_on_choice_pressed.bind(i))
		_choice_style(btn, i == selected_index)
		_choices_container.add_child(btn)
		_choice_buttons.append(btn)

func _choice_style(btn: Button, is_sel: bool) -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color("fde68a") if is_sel else Color(1.0, 1.0, 1.0, 0.06)
	s.corner_radius_top_left = 26; s.corner_radius_top_right = 26
	s.corner_radius_bottom_left = 26; s.corner_radius_bottom_right = 26
	s.content_margin_left = 20; s.content_margin_right = 20
	s.content_margin_top = 12; s.content_margin_bottom = 12
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.border_color = Color("fde68a") if is_sel else Color(214.0 / 255.0, 197.0 / 255.0, 168.0 / 255.0, 0.14)
	btn.add_theme_stylebox_override("normal", s)
	btn.add_theme_color_override("font_color", Color("0c0a09") if is_sel else Color("f5efe7"))
	btn.add_theme_font_size_override("font_size", 15)

func _on_choice_pressed(index: int) -> void:
	selected_index = index
	_rebuild_choices()
	choice_selected.emit(index)

func _on_overlay(event: InputEvent) -> void:
	if event is InputEventMouseButton and event.pressed:
		pass

func show_choice(title: String, text: String, opts: Array[String]) -> void:
	dialog_title = title
	dialog_text = text
	choices = opts
	show()
