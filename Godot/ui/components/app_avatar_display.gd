extends PanelContainer
class_name AppAvatarDisplay

@export var body_key: String = "human-female":
	set(v): body_key = v

@export var outfit_preset_key: String = "":
	set(v): outfit_preset_key = v

@export var avatar_scale: float = 1.22:
	set(v):
		avatar_scale = v
		if is_node_ready(): _avatar_sprite.scale = Vector2(v, v)

@onready var _avatar_sprite: TextureRect = %AvatarSprite
@onready var _name_label: Label = %NameLabel
@onready var _class_label: Label = %ClassLabel

func _ready() -> void:
	_avatar_sprite.expand_mode = TextureRect.EXPAND_FIT_WIDTH_PROPORTIONAL
	_avatar_sprite.stretch_mode = TextureRect.STRETCH_KEEP_ASPECT_CENTERED
	_avatar_sprite.scale = Vector2(avatar_scale, avatar_scale)

func set_sprite(texture: Texture2D) -> void:
	_avatar_sprite.texture = texture

func set_info(nickname: String, class_label: String) -> void:
	_name_label.text = nickname
	_name_label.add_theme_color_override("font_color", Color("f5f5f4"))
	_class_label.text = class_label
	_class_label.add_theme_color_override("font_color", Color("d6d3d1"))
