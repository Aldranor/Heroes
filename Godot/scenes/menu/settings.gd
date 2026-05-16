extends Control

@onready var _music_slider: HSlider = %MusicSlider
@onready var _sfx_slider: HSlider = %SfxSlider
@onready var _lang_dropdown: OptionButton = %LangDropdown
@onready var _email_label: Label = %EmailLabel
@onready var _logout_button: PixelButton = %LogoutButton
@onready var _back_button: Button = %BackButton
@onready var _reduce_anim_toggle: CheckButton = %ReduceAnimToggle
@onready var _version_label: Label = %VersionLabel

func _ready() -> void:
	_logout_button.pressed.connect(_on_logout_pressed)
	_back_button.pressed.connect(_on_back_pressed)
	_version_label.text = "v%s" % ProjectSettings.get_setting("application/config/version", "1.0")
	_email_label.text = GameState.current_user.email if GameState.current_user.email != "" else "non connecte"

func _on_logout_pressed() -> void:
	_set_form_enabled(false)
	LoadingOverlay.show("Deconnexion...")
	await AuthService.logout()
	GameState.clear()
	LoadingOverlay.hide()
	SceneManager.go_to_login()

func _on_back_pressed() -> void:
	SceneManager.go_to_menu(SceneManager.TransitionStyle.SLIDE_RIGHT)

func _set_form_enabled(enabled: bool) -> void:
	_logout_button.disabled = not enabled
	_back_button.disabled = not enabled
