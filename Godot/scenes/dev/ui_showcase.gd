extends Control

@onready var _hp_bar: AppStatBar = %HpBar
@onready var _mp_bar: AppStatBar = %MpBar
@onready var _xp_bar: AppStatBar = %XpBar
@onready var _scroll: ScrollContainer = %Scroll
@onready var _content_vbox: VBoxContainer = %ContentVBox
@onready var _dialog_box: AppDialogBox = %DialogBox
@onready var _tab_bar: AppTabBar = %BottomTabBar
@onready var _toast_button: AppButton = %ToastButton
@onready var _modal_button: AppButton = %ModalButton
@onready var _loading_button: AppButton = %LoadingButton
@onready var _primary_button: AppButton = %PrimaryButton
@onready var _secondary_button: AppButton = %SecondaryButton
@onready var _danger_button: AppButton = %DangerButton
@onready var _ghost_button: AppButton = %GhostButton
@onready var _disabled_button: AppButton = %DisabledButton

func _ready() -> void:
	_scroll.resized.connect(_on_scroll_resized)
	_fit_content_width()
	Callable(self, "_reset_scroll_x").call_deferred()
	_apply_showcase_variants()
	_hp_bar.bar_type = AppStatBar.BarType.HP
	_mp_bar.bar_type = AppStatBar.BarType.MP
	_xp_bar.bar_type = AppStatBar.BarType.XP
	_hp_bar.set_values(78, 100)
	_mp_bar.set_values(45, 100)
	_xp_bar.set_values(22, 100)

	_dialog_box.character_name = "Moniteur"
	_dialog_box.dialog_text = "Bienvenue sur l'interface Codex. Teste chaque composant tactile."
	_dialog_box.continued.connect(_on_dialog_finished)
	_tab_bar.tab_changed.connect(_on_tab_changed)

	_toast_button.pressed.connect(_on_toast_pressed)
	_modal_button.pressed.connect(_on_modal_pressed)
	_loading_button.pressed.connect(_on_loading_pressed)

func _apply_showcase_variants() -> void:
	_primary_button.variant = AppButton.Variant.PRIMARY
	_secondary_button.variant = AppButton.Variant.SECONDARY
	_danger_button.variant = AppButton.Variant.DANGER
	_ghost_button.variant = AppButton.Variant.GHOST
	_disabled_button.variant = AppButton.Variant.DISABLED
	for button: AppButton in [_primary_button, _secondary_button, _danger_button, _ghost_button, _disabled_button, _toast_button, _modal_button, _loading_button]:
		button.clip_text = false
	_tab_bar.use_rpg_assets = false

func _on_scroll_resized() -> void:
	_fit_content_width()
	_reset_scroll_x()

func _fit_content_width() -> void:
	var target_width: float = max(0.0, _scroll.size.x - 12.0)
	_content_vbox.custom_minimum_size.x = target_width

func _reset_scroll_x() -> void:
	_scroll.scroll_horizontal = 0

func _on_dialog_finished() -> void:
	_dialog_box.character_name = "Moniteur"
	_dialog_box.dialog_text = "Dialogue terminé. Clique encore pour rejouer l'effet typewriter."

func _on_tab_changed(tab_index: int) -> void:
	AppToast.show_toast("Onglet actif: %d" % (tab_index + 1), AppToast.Type.INFO)

func _on_toast_pressed() -> void:
	AppToast.show_toast("AppToast test (SUCCESS)", AppToast.Type.SUCCESS)

func _on_modal_pressed() -> void:
	var ok: bool = await AppModal.confirm("Quitter le combat ?", "Cette action n'est pas réversible.")
	if ok:
		AppToast.show_toast("Confirmation validée", AppToast.Type.SUCCESS)
	else:
		AppToast.show_toast("Action annulée", AppToast.Type.WARNING)

func _on_loading_pressed() -> void:
	LoadingOverlay.show("Chargement de la zone...")
	await get_tree().create_timer(1.4).timeout
	LoadingOverlay.hide()
	AppToast.show_toast("Chargement terminé", AppToast.Type.INFO)
