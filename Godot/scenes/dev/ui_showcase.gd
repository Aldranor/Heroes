extends Control

const RPG_TILES_TEXTURE: Texture2D = preload("res://assets/sprites/ui/rpg/Main_tiles.png")
@export var enable_rpg_skin_preview: bool = false

@onready var _hp_bar: StatBar = %HpBar
@onready var _mp_bar: StatBar = %MpBar
@onready var _xp_bar: StatBar = %XpBar
@onready var _scroll: ScrollContainer = %Scroll
@onready var _content_vbox: VBoxContainer = %ContentVBox
@onready var _dialog_box: DialogBox = %DialogBox
@onready var _tab_bar: PixelTabBar = %BottomTabBar
@onready var _toast_button: PixelButton = %ToastButton
@onready var _modal_button: PixelButton = %ModalButton
@onready var _loading_button: PixelButton = %LoadingButton
@onready var _primary_button: PixelButton = %PrimaryButton
@onready var _secondary_button: PixelButton = %SecondaryButton
@onready var _danger_button: PixelButton = %DangerButton
@onready var _sprite_preview_panel: PanelContainer = %SpritePreviewPanel

func _ready() -> void:
	_scroll.resized.connect(_on_scroll_resized)
	_fit_content_width()
	Callable(self, "_reset_scroll_x").call_deferred()
	if enable_rpg_skin_preview:
		_apply_rpg_ui_skin()
	else:
		_apply_stable_ui_skin()
	_hp_bar.bar_type = StatBar.BarType.HP
	_mp_bar.bar_type = StatBar.BarType.MP
	_xp_bar.bar_type = StatBar.BarType.XP
	_hp_bar.set_values(78, 100)
	_mp_bar.set_values(45, 100)
	_xp_bar.set_values(22, 100)

	_dialog_box.set_dialog("Moniteur", "Bienvenue sur l'interface Codex. Teste chaque composant tactile.", null)
	_dialog_box.dialog_finished.connect(_on_dialog_finished)
	_tab_bar.tab_changed.connect(_on_tab_changed)

	_toast_button.pressed.connect(_on_toast_pressed)
	_modal_button.pressed.connect(_on_modal_pressed)
	_loading_button.pressed.connect(_on_loading_pressed)

func _apply_rpg_ui_skin() -> void:
	for button: PixelButton in [_primary_button, _secondary_button, _danger_button, _toast_button, _modal_button, _loading_button]:
		button.use_rpg_assets = true
		button.clip_text = false

	_tab_bar.use_rpg_assets = true

	var panel_style: StyleBoxTexture = StyleBoxTexture.new()
	panel_style.texture = RPG_TILES_TEXTURE
	panel_style.region_rect = Rect2(128, 80, 48, 48)
	panel_style.texture_margin_left = 8
	panel_style.texture_margin_top = 8
	panel_style.texture_margin_right = 8
	panel_style.texture_margin_bottom = 8
	panel_style.content_margin_left = 8
	panel_style.content_margin_top = 8
	panel_style.content_margin_right = 8
	panel_style.content_margin_bottom = 8
	_dialog_box.add_theme_stylebox_override("panel", panel_style)
	_sprite_preview_panel.add_theme_stylebox_override("panel", panel_style)

func _apply_stable_ui_skin() -> void:
	for button: PixelButton in [_primary_button, _secondary_button, _danger_button, _toast_button, _modal_button, _loading_button]:
		button.use_rpg_assets = false
		button.clip_text = false
	_tab_bar.use_rpg_assets = false
	_dialog_box.remove_theme_stylebox_override("panel")
	_sprite_preview_panel.remove_theme_stylebox_override("panel")

func _on_scroll_resized() -> void:
	_fit_content_width()
	_reset_scroll_x()

func _fit_content_width() -> void:
	var target_width: float = max(0.0, _scroll.size.x - 12.0)
	_content_vbox.custom_minimum_size.x = target_width

func _reset_scroll_x() -> void:
	_scroll.scroll_horizontal = 0

func _on_dialog_finished() -> void:
	_dialog_box.set_dialog("Moniteur", "Dialogue terminé. Clique encore pour rejouer l'effet typewriter.", null)

func _on_tab_changed(tab_index: int) -> void:
	Toast.show_toast("Onglet actif: %d" % (tab_index + 1), Toast.Type.INFO)

func _on_toast_pressed() -> void:
	Toast.show_toast("Toast test (SUCCESS)", Toast.Type.SUCCESS)

func _on_modal_pressed() -> void:
	var ok: bool = await Modal.confirm("Quitter le combat ?", "Cette action n'est pas réversible.")
	if ok:
		Toast.show_toast("Confirmation validée", Toast.Type.SUCCESS)
	else:
		Toast.show_toast("Action annulée", Toast.Type.WARNING)

func _on_loading_pressed() -> void:
	LoadingOverlay.show("Chargement de la zone...")
	await get_tree().create_timer(1.4).timeout
	LoadingOverlay.hide()
	Toast.show_toast("Chargement terminé", Toast.Type.INFO)
