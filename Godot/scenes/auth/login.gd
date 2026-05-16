extends Control

@onready var _email_input: LineEdit = %EmailInput
@onready var _password_input: LineEdit = %PasswordInput
@onready var _login_button: AppButton = %LoginButton
@onready var _register_button: AppButton = %RegisterButton
@onready var _forgot_link: Button = %ForgotLink
@onready var _error_label: Label = %ErrorLabel

func _ready() -> void:
	_login_button.pressed.connect(_on_login_pressed)
	_register_button.pressed.connect(_on_register_pressed)
	_forgot_link.pressed.connect(_on_forgot_pressed)
	_error_label.text = ""

func _on_login_pressed() -> void:
	var email: String = _email_input.text.strip_edges()
	var password: String = _password_input.text

	if not _validate_email(email):
		_error_label.text = "Email invalide"
		return
	if password.is_empty():
		_error_label.text = "Mot de passe requis"
		return

	_error_label.text = ""
	_set_form_enabled(false)
	LoadingOverlay.show("Connexion...")

	var response: ApiResponse = await AuthService.login(email, password)

	LoadingOverlay.hide()
	_set_form_enabled(true)

	if response.success:
		await SceneManager.go_to_menu()
	else:
		var msg: String = str(response.data.get("message", "Erreur de connexion"))
		AppToast.show_toast(msg, AppToast.Type.ERROR)
		_error_label.text = msg

func _on_register_pressed() -> void:
	SceneManager.go_to_register(SceneManager.TransitionStyle.SLIDE_LEFT)

func _on_forgot_pressed() -> void:
	AppToast.show_toast("Fonctionnalite a venir", AppToast.Type.INFO)

func _validate_email(email: String) -> bool:
	return email.contains("@") and email.contains(".")

func _set_form_enabled(enabled: bool) -> void:
	_login_button.disabled = not enabled
	_register_button.disabled = not enabled
	_email_input.editable = enabled
	_password_input.editable = enabled

func _input(event: InputEvent) -> void:
	if event is InputEventKey and event.keycode == KEY_ENTER and event.pressed:
		_on_login_pressed()
