extends Control
class_name AppRewardPopup

signal dismissed

const FONT_TITLE: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_BODY: Font = preload("res://assets/fonts/inter_regular.tres")

@export var reward_title: String = "":
	set(v):
		reward_title = v
		if is_node_ready(): _title_label.text = v

@export var reward_description: String = "":
	set(v):
		reward_description = v
		if is_node_ready(): _desc_label.text = v

@export var reward_icon: String = "★":
	set(v):
		reward_icon = v
		if is_node_ready(): _icon_label.text = v

@onready var _overlay: ColorRect = %Overlay
@onready var _card: PanelContainer = %Card
@onready var _icon_label: Label = %IconLabel
@onready var _title_label: Label = %TitleLabel
@onready var _desc_label: Label = %DescriptionLabel
@onready var _confirm_button: Button = %ConfirmButton

func _ready() -> void:
	_style()
	_title_label.text = reward_title
	_desc_label.text = reward_description
	_icon_label.text = reward_icon
	_confirm_button.pressed.connect(_on_confirm)
	_overlay.gui_input.connect(_on_overlay_tap)
	_animate_in()

func _style() -> void:
	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 28)
	_title_label.add_theme_color_override("font_color", Color("fde68a"))
	_title_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_desc_label.add_theme_font_override("font", FONT_BODY)
	_desc_label.add_theme_font_size_override("font_size", 14)
	_desc_label.add_theme_color_override("font_color", Color("a8a29e"))
	_desc_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_desc_label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
	_icon_label.add_theme_font_size_override("font_size", 48)
	_icon_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER

	var card_s := StyleBoxFlat.new()
	card_s.bg_color = Color(37.0 / 255.0, 31.0 / 255.0, 28.0 / 255.0, 0.98)
	card_s.border_color = Color(201.0 / 255.0, 168.0 / 255.0, 122.0 / 255.0, 0.3)
	card_s.border_width_left = 1; card_s.border_width_top = 1
	card_s.border_width_right = 1; card_s.border_width_bottom = 1
	card_s.corner_radius_top_left = 32; card_s.corner_radius_top_right = 32
	card_s.corner_radius_bottom_left = 32; card_s.corner_radius_bottom_right = 32
	card_s.content_margin_left = 28; card_s.content_margin_right = 28
	card_s.content_margin_top = 28; card_s.content_margin_bottom = 28
	card_s.shadow_size = 40; card_s.shadow_color = Color(0, 0, 0, 0.3)
	card_s.shadow_offset = Vector2(0, 20)
	_card.add_theme_stylebox_override("panel", card_s)

func _animate_in() -> void:
	_overlay.modulate.a = 0.0
	_card.scale = Vector2(0.8, 0.8)
	_card.modulate.a = 0.0
	var t := create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_QUINT)
	t.tween_property(_overlay, "modulate:a", 1.0, 0.3)
	t.parallel().tween_property(_card, "scale", Vector2.ONE, 0.35)
	t.parallel().tween_property(_card, "modulate:a", 1.0, 0.25)

func show_reward(title: String, description: String, icon: String) -> void:
	reward_title = title
	reward_description = description
	reward_icon = icon
	show()

func _on_confirm() -> void:
	dismissed.emit()
	queue_free()

func _on_overlay_tap(event: InputEvent) -> void:
	if event is InputEventMouseButton and event.pressed:
		_on_confirm()
