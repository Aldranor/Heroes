extends Control

const CARDS: Array[Dictionary] = [
	{"label": "Continuer l'aventure", "icon": "→", "scene": "dashboard"},
	{"label": "Entrainement", "icon": "⚔", "scene": ""},
	{"label": "Compagnons", "icon": "♥", "scene": ""},
	{"label": "Boutique", "icon": "★", "scene": ""},
	{"label": "Quetes", "icon": "!", "scene": ""},
	{"label": "Leaderboard", "icon": "▲", "scene": ""},
]

@onready var _avatar_rect: TextureRect = %AvatarRect
@onready var _name_label: Label = %NameLabel
@onready var _level_label: Label = %LevelLabel
@onready var _xp_bar: StatBar = %XpBar
@onready var _coins_label: Label = %CoinsLabel
@onready var _card_container: VBoxContainer = %CardContainer
@onready var _settings_button: Button = %SettingsButton

func _ready() -> void:
	GameState.user_changed.connect(_on_user_changed)
	GameState.coins_changed.connect(_on_coins_changed)
	_settings_button.pressed.connect(_on_settings_pressed)
	_build_cards()
	_refresh_user_display()

func _build_cards() -> void:
	for card_data: Dictionary in CARDS:
		var card: PanelContainer = _create_card(card_data)
		_card_container.add_child(card)

func _create_card(data: Dictionary) -> PanelContainer:
	var panel: PanelContainer = PanelContainer.new()
	panel.custom_minimum_size = Vector2(0, 60)
	panel.mouse_filter = Control.MOUSE_FILTER_PASS
	panel.gui_input.connect(_on_card_gui_input.bind(data.get("scene", ""), panel))

	var hbox: HBoxContainer = HBoxContainer.new()
	hbox.size_flags_horizontal = 3
	hbox.offset_left = 8
	panel.add_child(hbox)

	var icon_label: Label = Label.new()
	icon_label.text = str(data.get("icon", "•"))
	icon_label.custom_minimum_size = Vector2(36, 0)
	icon_label.size_flags_horizontal = 0
	icon_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
	icon_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	hbox.add_child(icon_label)

	var text_label: Label = Label.new()
	text_label.text = str(data.get("label", ""))
	text_label.size_flags_horizontal = 3
	text_label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
	hbox.add_child(text_label)

	return panel

func _on_card_gui_input(event: InputEvent, scene_key: String, panel: PanelContainer) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		_navigate_to_card(scene_key)
	if event is InputEventScreenTouch and event.pressed:
		_navigate_to_card(scene_key)

func _navigate_to_card(scene_key: String) -> void:
	match scene_key:
		"dashboard":
			SceneManager.go_to_dashboard()
		_:
			Toast.show_toast("Coming soon!", Toast.Type.INFO)

func _on_settings_pressed() -> void:
	SceneManager.go_to_settings(SceneManager.TransitionStyle.SLIDE_LEFT)

func _on_user_changed(user: User) -> void:
	_refresh_user_display()

func _on_coins_changed(amount: int) -> void:
	_coins_label.text = "★ %d" % amount

func _refresh_user_display() -> void:
	var user: User = GameState.current_user
	_name_label.text = user.name if user.name != "" else "Aventurier"
	_level_label.text = "Niv. %d" % user.level
	_xp_bar.set_values(user.xp, user.xp_to_next_level)
	_coins_label.text = "★ %d" % user.coins
