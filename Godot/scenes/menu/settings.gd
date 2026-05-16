extends Control

@onready var _music_slider: HSlider = %MusicSlider
@onready var _sfx_slider: HSlider = %SfxSlider
@onready var _lang_dropdown: OptionButton = %LangDropdown
@onready var _email_label: Label = %EmailLabel
@onready var _logout_button: AppButton = %LogoutButton
@onready var _back_button: Button = %BackButton
@onready var _reduce_anim_toggle: CheckButton = %ReduceAnimToggle
@onready var _version_label: Label = %VersionLabel

func _ready() -> void:
	_logout_button.pressed.connect(_on_logout_pressed)
	_back_button.pressed.connect(_on_back_pressed)
	_version_label.text = "v%s" % ProjectSettings.get_setting("application/config/version", "1.0")
	_email_label.text = GameState.current_user.email if GameState.current_user.email != "" else tr("non connecte")
	_setup_lang_dropdown()


func _setup_lang_dropdown() -> void:
	_lang_dropdown.clear()
	_lang_dropdown.add_item("Français")
	_lang_dropdown.add_item("English")
	_lang_dropdown.select(I18n.locale())
	_lang_dropdown.item_selected.connect(_on_lang_selected)


func _on_lang_selected(index: int) -> void:
	I18n.set_locale(index as I18n.Locale)
	_notify_locale_changed()


func _notify_locale_changed() -> void:
	_email_label.text = GameState.current_user.email if GameState.current_user.email != "" else tr("non connecte")

func _on_logout_pressed() -> void:
	_set_form_enabled(false)
	LoadingOverlay.show(tr("Deconnexion..."))
	await AuthService.logout()
	GameState.clear()
	LoadingOverlay.hide()
	SceneManager.go_to_login()

func _on_back_pressed() -> void:
	SceneManager.go_to_menu(SceneManager.TransitionStyle.SLIDE_RIGHT)

func _set_form_enabled(enabled: bool) -> void:
	_logout_button.disabled = not enabled
	_back_button.disabled = not enabled
