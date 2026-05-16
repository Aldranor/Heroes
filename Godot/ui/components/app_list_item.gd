extends PanelContainer
class_name AppListItem

const FONT_TITLE: Font = preload("res://assets/fonts/inter_medium.tres")
const FONT_DESC: Font = preload("res://assets/fonts/inter_regular.tres")

@export var icon_text: String = "":
	set(v):
		icon_text = v
		if is_node_ready(): _icon_label.text = v

@export var title_text: String = "":
	set(v):
		title_text = v
		if is_node_ready(): _title_label.text = v

@export var subtitle_text: String = "":
	set(v):
		subtitle_text = v
		if is_node_ready(): _subtitle_label.text = v

@export var trailing_text: String = "":
	set(v):
		trailing_text = v
		if is_node_ready(): _trailing_label.text = v

@export var highlight: bool = false:
	set(v):
		highlight = v
		if is_node_ready(): _apply_highlight()

@onready var _icon_container: PanelContainer = %IconContainer
@onready var _icon_label: Label = %IconLabel
@onready var _title_label: Label = %TitleLabel
@onready var _subtitle_label: Label = %SubtitleLabel
@onready var _trailing_label: Label = %TrailingLabel

func _ready() -> void:
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	_style_base()
	_style_panel()
	_icon_label.text = icon_text
	_title_label.text = title_text
	_subtitle_label.text = subtitle_text
	_trailing_label.text = trailing_text
	_apply_highlight()

func _style_base() -> void:
	_title_label.add_theme_font_override("font", FONT_TITLE)
	_title_label.add_theme_font_size_override("font_size", 15)
	_title_label.add_theme_color_override("font_color", Color("f5f5f4"))
	_title_label.clip_text = true

	_subtitle_label.add_theme_font_override("font", FONT_DESC)
	_subtitle_label.add_theme_font_size_override("font_size", 12)
	_subtitle_label.add_theme_color_override("font_color", Color("a8a29e"))
	_subtitle_label.clip_text = true

	_trailing_label.add_theme_font_override("font", FONT_DESC)
	_trailing_label.add_theme_font_size_override("font_size", 13)
	_trailing_label.add_theme_color_override("font_color", Color("fde68a"))

	_icon_label.add_theme_font_size_override("font_size", 18)
	_icon_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	_icon_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER

func _style_panel() -> void:
	var icon_s := StyleBoxFlat.new()
	icon_s.bg_color = Color(1.0, 1.0, 1.0, 0.08)
	icon_s.corner_radius_top_left = 12; icon_s.corner_radius_top_right = 12
	icon_s.corner_radius_bottom_left = 12; icon_s.corner_radius_bottom_right = 12
	_icon_container.add_theme_stylebox_override("panel", icon_s)

	var s := StyleBoxFlat.new()
	s.bg_color = Color.TRANSPARENT
	s.content_margin_left = 12; s.content_margin_right = 12
	s.content_margin_top = 10; s.content_margin_bottom = 10
	s.corner_radius_top_left = 16; s.corner_radius_top_right = 16
	s.corner_radius_bottom_left = 16; s.corner_radius_bottom_right = 16
	add_theme_stylebox_override("panel", s)

func _apply_highlight() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color("fde68a").darkened(0.8) if highlight else Color.TRANSPARENT
	s.border_color = Color("fde68a") if highlight else Color.TRANSPARENT
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 16; s.corner_radius_top_right = 16
	s.corner_radius_bottom_left = 16; s.corner_radius_bottom_right = 16
	s.content_margin_left = 12; s.content_margin_right = 12
	s.content_margin_top = 10; s.content_margin_bottom = 10
	add_theme_stylebox_override("panel", s)

func setup(icon: String, title: String, subtitle: String, trailing: String) -> void:
	icon_text = icon
	title_text = title
	subtitle_text = subtitle
	trailing_text = trailing
