extends PanelContainer
class_name PixelTabBar

const RPG_BUTTONS_TEXTURE: Texture2D = preload("res://assets/sprites/ui/rpg/Buttons.png")
const RPG_ICONS_TEXTURE: Texture2D = preload("res://assets/sprites/ui/rpg/Icons.png")

signal tab_changed(tab_index: int)

@export var tabs: Array[Dictionary] = [
	{"label": "Hub", "icon": null},
	{"label": "Map", "icon": null},
	{"label": "Train", "icon": null},
	{"label": "Bag", "icon": null},
	{"label": "Shop", "icon": null}
]

@export var active_tab: int = 0:
	set(value):
		active_tab = clampi(value, 0, 4)
		if is_node_ready():
			_refresh_buttons()

@export var use_rpg_assets: bool = false:
	set(value):
		use_rpg_assets = value
		if is_node_ready():
			_refresh_buttons()

@onready var _buttons: Array[Button] = [
	%Tab0,
	%Tab1,
	%Tab2,
	%Tab3,
	%Tab4
]

func _ready() -> void:
	for i: int in range(_buttons.size()):
		var button: Button = _buttons[i]
		button.custom_minimum_size = Vector2(44, 44)
		button.pressed.connect(_on_tab_pressed.bind(i))
	_refresh_buttons()

func _on_tab_pressed(index: int) -> void:
	active_tab = index
	_refresh_buttons()
	emit_signal("tab_changed", active_tab)

func _refresh_buttons() -> void:
	for i: int in range(_buttons.size()):
		var button: Button = _buttons[i]
		var data: Dictionary = {}
		if i < tabs.size() and tabs[i] is Dictionary:
			data = tabs[i]
		button.text = str(data.get("label", "Tab %d" % (i + 1)))
		var icon: Variant = data.get("icon", null)
		if icon is Texture2D:
			button.icon = icon
		elif use_rpg_assets:
			button.icon = _icon_for_index(i)
			button.expand_icon = true
		else:
			button.icon = null
		button.clip_text = true
		_apply_button_state(button, i == active_tab)

func _apply_button_state(button: Button, is_active: bool) -> void:
	if use_rpg_assets:
		var normal_rect: Rect2 = Rect2(224, 32, 64, 16)
		var active_rect: Rect2 = Rect2(296, 32, 64, 16)
		var sb: StyleBoxTexture = _build_rpg_style(active_rect if is_active else normal_rect)
		button.add_theme_stylebox_override("normal", sb)
		button.add_theme_stylebox_override("hover", sb)
		button.add_theme_stylebox_override("pressed", sb)
		button.add_theme_color_override("font_color", Color("0b0f0f"))
		button.add_theme_color_override("font_hover_color", Color("0b0f0f"))
		button.add_theme_color_override("font_pressed_color", Color("0b0f0f"))
		return

	var sb: StyleBoxFlat = StyleBoxFlat.new()
	sb.bg_color = Color("2d2d2d")
	sb.border_width_top = 2
	sb.border_width_left = 2
	sb.border_width_right = 2
	sb.border_width_bottom = 2
	sb.border_color = Color("4a4540")
	if is_active:
		sb.bg_color = Color("14110f")
		sb.border_color = Color("d4af37")
	button.add_theme_stylebox_override("normal", sb)
	button.add_theme_stylebox_override("hover", sb)
	button.add_theme_stylebox_override("pressed", sb)

func _build_rpg_style(region: Rect2) -> StyleBoxTexture:
	var sb: StyleBoxTexture = StyleBoxTexture.new()
	sb.texture = RPG_BUTTONS_TEXTURE
	sb.region_rect = region
	sb.texture_margin_left = 6
	sb.texture_margin_top = 6
	sb.texture_margin_right = 6
	sb.texture_margin_bottom = 6
	sb.content_margin_left = 6
	sb.content_margin_top = 6
	sb.content_margin_right = 6
	sb.content_margin_bottom = 6
	return sb

func _icon_for_index(index: int) -> AtlasTexture:
	var atlas: AtlasTexture = AtlasTexture.new()
	atlas.atlas = RPG_ICONS_TEXTURE
	match index:
		0:
			atlas.region = Rect2(16, 112, 16, 16) # home
		1:
			atlas.region = Rect2(32, 128, 16, 16) # map / arrow
		2:
			atlas.region = Rect2(112, 80, 16, 16) # sword
		3:
			atlas.region = Rect2(48, 96, 16, 16) # bag
		4:
			atlas.region = Rect2(16, 80, 16, 16) # shop coin
		_:
			atlas.region = Rect2(0, 0, 16, 16)
	return atlas
