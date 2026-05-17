extends Control

@onready var _bg: ColorRect = %Background
@onready var _email_input: LineEdit = %EmailInput
@onready var _password_input: LineEdit = %PasswordInput
@onready var _login_button: AppButton = %LoginButton
@onready var _forgot_link: LinkButton = %ForgotLink
@onready var _register_link: LinkButton = %RegisterLink
@onready var _remember_check: CheckBox = %RememberCheck
@onready var _error_label: Label = %ErrorLabel

func _ready() -> void:
	_bg.color = get_theme_color("background_darkest", "Palette")
	_login_button.pressed.connect(_on_login_pressed)
	_forgot_link.pressed.connect(_on_forgot_pressed)
	_register_link.pressed.connect(_on_register_pressed)

	_error_label.hide()

func _show_error(msg: String) -> void:
	_error_label.text = msg
	_error_label.add_theme_color_override("font_color", Color("fca5a5"))
	_error_label.show()

func _clear_error() -> void:
	_error_label.hide()
	_error_label.remove_theme_color_override("font_color")

func _on_login_pressed() -> void:
	var email: String = _email_input.text.strip_edges()
	var password: String = _password_input.text

	if not _validate_email(email):
		_show_error(tr("Adresse email invalide"))
		return
	if password.is_empty():
		_show_error(tr("Mot de passe requis"))
		return

	_clear_error()
	_set_form_enabled(false)
	LoadingOverlay.show(tr("Connexion..."))

	var response: ApiResponse = await AuthService.login(email, password)

	LoadingOverlay.hide()
	_set_form_enabled(true)

	if response.success:
		await SceneManager.go_to_menu()
	else:
		var msg: String = str(response.data.get("message", tr("Erreur de connexion")))
		_show_error(msg)
		AppToast.show_toast(msg, AppToast.Type.ERROR)

func _on_register_pressed() -> void:
	SceneManager.go_to_register(SceneManager.TransitionStyle.SLIDE_LEFT)

func _on_forgot_pressed() -> void:
	AppToast.show_toast(tr("Fonctionnalité à venir"), AppToast.Type.INFO)

func _validate_email(email: String) -> bool:
	return email.contains("@") and email.contains(".")

func _set_form_enabled(enabled: bool) -> void:
	_login_button.disabled = not enabled
	_register_link.disabled = not enabled
	_forgot_link.disabled = not enabled
	_remember_check.disabled = not enabled
	_email_input.editable = enabled
	_password_input.editable = enabled

func _input(event: InputEvent) -> void:
	if event is InputEventKey and event.keycode == KEY_ENTER and event.pressed:
		_on_login_pressed()
