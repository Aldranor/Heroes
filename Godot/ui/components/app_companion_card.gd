extends PanelContainer
class_name AppCompanionCard

const FONT_NAME: Font = preload("res://assets/fonts/inter_medium.tres")
const FONT_DESC: Font = preload("res://assets/fonts/inter_regular.tres")

@export var companion_name: String = "":
	set(v):
		companion_name = v
		if is_node_ready(): _name_label.text = v

@export var companion_role: String = "":
	set(v):
		companion_role = v
		if is_node_ready(): _role_label.text = v

@export var companion_level: int = 1:
	set(v):
		companion_level = v
		if is_node_ready(): _level_label.text = "Lv. %d" % v

@export var companion_hp: int = 100:
	set(v):
		companion_hp = v
		if is_node_ready(): _hp_label.text = str(v)

@export var accent_color: Color = Color("D97745"):
	set(v):
		accent_color = v
		if is_node_ready(): _apply_accent()

@export var sprite_texture: Texture2D:
	set(v):
		sprite_texture = v
		if is_node_ready(): _sprite_rect.texture = v

@onready var _sprite_rect: TextureRect = %SpriteRect
@onready var _name_label: Label = %NameLabel
@onready var _role_label: Label = %RoleLabel
@onready var _level_label: Label = %LevelLabel
@onready var _hp_label: Label = %HpLabel
@onready var _accent_badge: PanelContainer = %AccentBadge

func _ready() -> void:
	_style_panel()
	_style_labels()
	_name_label.text = companion_name
	_role_label.text = companion_role
	_level_label.text = "Lv. %d" % companion_level
	_hp_label.text = str(companion_hp)
	if sprite_texture:
		_sprite_rect.texture = sprite_texture
	_apply_accent()

func _style_panel() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = Color(52.0 / 255.0, 45.0 / 255.0, 40.0 / 255.0, 0.94)
	s.border_color = Color(214.0 / 255.0, 197.0 / 255.0, 168.0 / 255.0, 0.14)
	s.border_width_left = 1; s.border_width_top = 1
	s.border_width_right = 1; s.border_width_bottom = 1
	s.corner_radius_top_left = 27; s.corner_radius_top_right = 27
	s.corner_radius_bottom_left = 27; s.corner_radius_bottom_right = 27
	s.content_margin_left = 16; s.content_margin_right = 16
	s.content_margin_top = 16; s.content_margin_bottom = 16
	s.shadow_size = 28; s.shadow_color = Color(0, 0, 0, 0.16)
	s.shadow_offset = Vector2(0, 18)
	add_theme_stylebox_override("panel", s)

func _style_labels() -> void:
	_name_label.add_theme_font_override("font", FONT_NAME)
	_name_label.add_theme_font_size_override("font_size", 18)
	_name_label.add_theme_color_override("font_color", Color("f5f5f4"))
	_role_label.add_theme_font_override("font", FONT_DESC)
	_role_label.add_theme_font_size_override("font_size", 14)
	_role_label.add_theme_color_override("font_color", Color("a8a29e"))
	_level_label.add_theme_font_override("font", FONT_DESC)
	_level_label.add_theme_font_size_override("font_size", 11)
	_level_label.add_theme_color_override("font_color", Color("78716c"))
	_hp_label.add_theme_font_override("font", FONT_NAME)
	_hp_label.add_theme_font_size_override("font_size", 16)
	_hp_label.add_theme_color_override("font_color", Color("f5f5f4"))

func _apply_accent() -> void:
	var s := StyleBoxFlat.new()
	s.bg_color = accent_color
	s.corner_radius_top_left = 999; s.corner_radius_top_right = 999
	s.corner_radius_bottom_left = 999; s.corner_radius_bottom_right = 999
	_accent_badge.add_theme_stylebox_override("panel", s)

func setup(name_str: String, role_str: String, lvl: int, hp_val: int, color: Color) -> void:
	companion_name = name_str
	companion_role = role_str
	companion_level = lvl
	companion_hp = hp_val
	accent_color = color
