extends Control

@export var auto_layout: bool = false

var _test_sprites: Array[Dictionary] = []
var _current_texture: Texture2D = null

@onready var _sprite_selector: OptionButton = %SpriteSelector
@onready var _anim_selector: OptionButton = %AnimSelector
@onready var _sprite_animator: AppSpriteAnimator = %AppSpriteAnimator
@onready var _header: Label = $Header
@onready var _control_panel: PanelContainer = $ControlPanel
@onready var _status_bar: PanelContainer = $StatusBar
@onready var _speed_slider: HSlider = %SpeedSlider
@onready var _speed_label: Label = %SpeedLabel
@onready var _flip_h_check: CheckButton = %FlipHCheck
@onready var _flip_v_check: CheckButton = %FlipVCheck
@onready var _status_label: Label = %StatusLabel
@onready var _compose_button: Button = %ComposeButton
@onready var _composed_display: Sprite2D = %ComposedDisplay
@onready var _remote_url: LineEdit = %RemoteUrl
@onready var _load_remote_button: Button = %LoadRemoteButton

var _display_panel: PanelContainer

func _ready() -> void:
	_display_panel = _resolve_display_panel()
	if _display_panel == null:
		push_error("SpriteTest: display panel node introuvable (DisplayPanel/PanelContainer).")
		return

	if auto_layout:
		resized.connect(_on_root_resized)
		_apply_responsive_layout()
	_scan_test_sprites()
	_sprite_selector.item_selected.connect(_on_sprite_selected)
	_anim_selector.item_selected.connect(_on_anim_selected)
	_speed_slider.value_changed.connect(_on_speed_changed)
	_flip_h_check.toggled.connect(_on_flip_h_toggled)
	_flip_v_check.toggled.connect(_on_flip_v_toggled)
	_compose_button.pressed.connect(_on_compose_pressed)
	_load_remote_button.pressed.connect(_on_load_remote_pressed)

	if _test_sprites.size() > 0:
		_sprite_selector.select(0)
		_on_sprite_selected(0)

func _on_root_resized() -> void:
	_apply_responsive_layout()

func _apply_responsive_layout() -> void:
	if _header == null or _control_panel == null or _status_bar == null or _display_panel == null:
		return

	var margin_px: float = 8.0
	var gap_px: float = 8.0
	var header_height: float = 24.0
	var status_height: float = 26.0
	var top_y: float = margin_px + header_height + 6.0
	var bottom_y: float = size.y - status_height - margin_px
	var left_panel_width: float = clampf(size.x * 0.42, 132.0, 220.0)

	# Header (full width)
	_header.anchor_left = 0.0
	_header.anchor_top = 0.0
	_header.anchor_right = 1.0
	_header.anchor_bottom = 0.0
	_header.offset_left = margin_px
	_header.offset_top = margin_px
	_header.offset_right = -margin_px
	_header.offset_bottom = margin_px + header_height

	# Left control panel
	_control_panel.anchor_left = 0.0
	_control_panel.anchor_top = 0.0
	_control_panel.anchor_right = 0.0
	_control_panel.anchor_bottom = 0.0
	_control_panel.offset_left = margin_px
	_control_panel.offset_top = top_y
	_control_panel.offset_right = margin_px + left_panel_width
	_control_panel.offset_bottom = bottom_y

	# Main display panel
	_display_panel.anchor_left = 0.0
	_display_panel.anchor_top = 0.0
	_display_panel.anchor_right = 1.0
	_display_panel.anchor_bottom = 0.0
	_display_panel.offset_left = margin_px + left_panel_width + gap_px
	_display_panel.offset_top = top_y
	_display_panel.offset_right = -margin_px
	_display_panel.offset_bottom = bottom_y

	# Bottom status bar
	_status_bar.anchor_left = 0.0
	_status_bar.anchor_top = 1.0
	_status_bar.anchor_right = 1.0
	_status_bar.anchor_bottom = 1.0
	_status_bar.offset_left = margin_px
	_status_bar.offset_top = -status_height - margin_px
	_status_bar.offset_right = -margin_px
	_status_bar.offset_bottom = -margin_px

	_center_animator_in_display()

func _center_animator_in_display() -> void:
	if _display_panel == null:
		return
	_sprite_animator.position = _display_panel.size * 0.5

func _resolve_display_panel() -> PanelContainer:
	var display_panel: Node = get_node_or_null("DisplayPanel")
	if display_panel == null:
		display_panel = get_node_or_null("PanelContainer")
	return display_panel as PanelContainer

func _scan_test_sprites() -> void:
	var base_path: String = "res://assets/sprites/"
	var dir: DirAccess = DirAccess.open(base_path)
	if dir == null:
		_status_label.text = "Aucun sprite trouvé dans assets/sprites/"
		return

	dir.list_dir_begin()
	var file_name: String = dir.get_next()
	while file_name != "":
		var ext: String = file_name.get_extension().to_lower()
		if not dir.current_is_dir() and (ext == "png" or ext == "webp"):
			var full_path: String = base_path.path_join(file_name)
			var display_name: String = file_name.get_basename().capitalize()
			_sprite_selector.add_item(display_name)
			_test_sprites.append({"name": display_name, "path": full_path})
		file_name = dir.get_next()
	dir.list_dir_end()

	if _test_sprites.is_empty():
		_status_label.text = "Aucun PNG trouvé dans assets/sprites/"
	else:
		_status_label.text = "%d sprite(s) trouvé(s)" % _test_sprites.size()

func _on_sprite_selected(index: int) -> void:
	if index < 0 or index >= _test_sprites.size():
		return
	var entry: Dictionary = _test_sprites[index]
	_current_texture = SpriteLoader.load_local(entry["path"])
	if _current_texture == null:
		_status_label.text = "Erreur: impossible de charger %s" % entry["path"]
		return

	SpriteLoader.configure_animator(_sprite_animator, _current_texture)
	_center_animator_in_display()
	_populate_anim_selector()
	_status_label.text = "Chargé: %s (%dx%d)" % [
		entry["name"],
		_current_texture.get_width(),
		_current_texture.get_height()
	]

func _populate_anim_selector() -> void:
	_anim_selector.clear()
	var states: Dictionary = _sprite_animator.animation_states
	for anim_name: String in states:
		_anim_selector.add_item(anim_name)
	if _anim_selector.item_count > 0:
		_anim_selector.select(0)
		_on_anim_selected(0)

func _on_anim_selected(index: int) -> void:
	var anim_name: String = _anim_selector.get_item_text(index)
	_sprite_animator.play(anim_name)
	_status_label.text = "Animation: %s" % anim_name

func _on_speed_changed(value: float) -> void:
	_speed_label.text = "Vitesse: %.1fx" % value
	Engine.time_scale = max(value, 0.01)

func _on_flip_h_toggled(flipped: bool) -> void:
	var sprite: Sprite2D = _sprite_animator.get_node("%Sprite")
	if sprite != null:
		sprite.flip_h = flipped

func _on_flip_v_toggled(flipped: bool) -> void:
	var sprite: Sprite2D = _sprite_animator.get_node("%Sprite")
	if sprite != null:
		sprite.flip_v = flipped

func _on_load_remote_pressed() -> void:
	var url: String = _remote_url.text.strip_edges()
	if url.is_empty():
		_status_label.text = "Entrez une URL distante"
		return

	_status_label.text = "Téléchargement en cours..."
	_load_remote_button.disabled = true
	_current_texture = await SpriteLoader.load_remote(url)
	_load_remote_button.disabled = false

	if _current_texture == null:
		_status_label.text = "Erreur: echec du chargement distant"
		return

	SpriteLoader.configure_animator(_sprite_animator, _current_texture)
	_populate_anim_selector()
	_status_label.text = "Charge depuis URL: %s (%dx%d) - cache OK" % [
		url,
		_current_texture.get_width(),
		_current_texture.get_height()
	]

func _on_compose_pressed() -> void:
	if _test_sprites.size() < 2:
		_status_label.text = " besoin d'au moins 2 sprites pour composer"
		return

	var base_tex: Texture2D = SpriteLoader.load_local(_test_sprites[0]["path"])
	var overlay_tex: Texture2D = SpriteLoader.load_local(_test_sprites[1]["path"])
	if base_tex == null or overlay_tex == null:
		return

	var result: ImageTexture = SpriteCompositor.compose(base_tex, [overlay_tex])
	if result != null:
		_composed_display.texture = result
		_composed_display.visible = true
		_status_label.text = "Composition réussie"
