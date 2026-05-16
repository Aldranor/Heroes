extends Control

const ZONES: Array[Dictionary] = [
	{"label": "Carte du Monde", "icon": "🌍", "scene": ""},
	{"label": "Donjons", "icon": "🏰", "scene": ""},
	{"label": "Equipe", "icon": "♥", "scene": ""},
	{"label": "Inventaire", "icon": "★", "scene": ""},
]

@onready var _back_button: Button = %BackButton
@onready var _settings_button: Button = %SettingsButton
@onready var _avatar_rect: TextureRect = %AvatarRect
@onready var _name_label: Label = %NameLabel
@onready var _level_label: Label = %LevelLabel
@onready var _xp_bar: AppStatBar = %XpBar
@onready var _coins_label: Label = %CoinsLabel
@onready var _zone_container: GridContainer = %ZoneContainer

func _ready() -> void:
	_back_button.pressed.connect(_on_back_pressed)
	_settings_button.pressed.connect(_on_settings_pressed)
	GameState.user_changed.connect(_on_user_changed)
	GameState.coins_changed.connect(_on_coins_changed)
	_build_zones()
	_refresh_user_display()

func _build_zones() -> void:
	for zone_data: Dictionary in ZONES:
		var card: PanelContainer = _create_zone_card(zone_data)
		_zone_container.add_child(card)

func _create_zone_card(data: Dictionary) -> PanelContainer:
	var panel: PanelContainer = PanelContainer.new()
	panel.custom_minimum_size = Vector2(0, 72)
	panel.mouse_filter = Control.MOUSE_FILTER_PASS
	panel.gui_input.connect(_on_zone_gui_input.bind(data.get("scene", ""), panel))

	var hbox: HBoxContainer = HBoxContainer.new()
	hbox.size_flags_horizontal = 3
	panel.add_child(hbox)

	var icon_label: Label = Label.new()
	icon_label.text = str(data.get("icon", "•"))
	icon_label.custom_minimum_size = Vector2(44, 0)
	icon_label.size_flags_horizontal = 0
	icon_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	icon_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	hbox.add_child(icon_label)

	var text_label: Label = Label.new()
	text_label.text = tr(str(data.get("label", "")))
	text_label.size_flags_horizontal = 3
	text_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	hbox.add_child(text_label)

	return panel

func _on_zone_gui_input(event: InputEvent, _scene_key: String, panel: PanelContainer) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		_navigate_to_zone(_scene_key, panel)
	if event is InputEventScreenTouch and event.pressed:
		_navigate_to_zone(_scene_key, panel)

func _navigate_to_zone(_scene_key: String, _panel: PanelContainer) -> void:
	AppToast.show_toast(tr("Coming soon!"), AppToast.Type.INFO)

func _on_back_pressed() -> void:
	SceneManager.go_to_menu(SceneManager.TransitionStyle.SLIDE_RIGHT)

func _on_settings_pressed() -> void:
	SceneManager.go_to_settings(SceneManager.TransitionStyle.SLIDE_LEFT)

func _on_user_changed(user: User) -> void:
	_refresh_user_display()

func _on_coins_changed(amount: int) -> void:
	_coins_label.text = "★ %d" % amount

func _refresh_user_display() -> void:
	var user: User = GameState.current_user
	_name_label.text = user.name if user.name != "" else tr("Aventurier")
	_level_label.text = tr("Niv. %d") % user.level
	_xp_bar.set_values(user.xp, user.xp_to_next_level)
	_coins_label.text = "★ %d" % user.coins

func _input(event: InputEvent) -> void:
	if event.is_action_pressed("ui_back"):
		get_viewport().set_input_as_handled()
		_on_back_pressed()
