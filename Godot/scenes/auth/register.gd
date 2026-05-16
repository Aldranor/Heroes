extends Control

@onready var _name_input: LineEdit = %NameInput
@onready var _email_input: LineEdit = %EmailInput
@onready var _password_input: LineEdit = %PasswordInput
@onready var _confirm_input: LineEdit = %ConfirmInput
@onready var _register_button: AppButton = %RegisterButton
@onready var _back_button: AppButton = %BackButton
@onready var _error_label: Label = %ErrorLabel

func _ready() -> void:
	_register_button.pressed.connect(_on_register_pressed)
	_back_button.pressed.connect(_on_back_pressed)
	_error_label.text = ""

func _on_register_pressed() -> void:
	var display_name: String = _name_input.text.strip_edges()
	var email: String = _email_input.text.strip_edges()
	var password: String = _password_input.text
	var confirm: String = _confirm_input.text

	if display_name.is_empty():
		_error_label.text = tr("Nom requis")
		return
	if not _validate_email(email):
		_error_label.text = tr("Adresse email invalide")
		return
	if password.length() < 8:
		_error_label.text = tr("8 caracteres minimum")
		return
	if password != confirm:
		_error_label.text = tr("Mots de passe differents")
		return

	_error_label.text = ""
	_set_form_enabled(false)
	LoadingOverlay.show(tr("Inscription..."))

	var response: ApiResponse = await AuthService.register(email, password, display_name)

	LoadingOverlay.hide()
	_set_form_enabled(true)

	if response.success:
		var token: String = ""
		if response.data is Dictionary:
			token = str(response.data.get("token", ""))
		if token != "":
			ApiClient.set_auth_token(token)
		await SceneManager.go_to_menu()
	else:
		var msg: String = str(response.data.get("message", tr("Erreur d'inscription")))
		AppToast.show_toast(msg, AppToast.Type.ERROR)
		_error_label.text = msg

func _on_back_pressed() -> void:
	SceneManager.go_to_login(SceneManager.TransitionStyle.SLIDE_RIGHT)

func _validate_email(email: String) -> bool:
	return email.contains("@") and email.contains(".")

func _set_form_enabled(enabled: bool) -> void:
	_register_button.disabled = not enabled
	_back_button.disabled = not enabled
	_name_input.editable = enabled
	_email_input.editable = enabled
	_password_input.editable = enabled
	_confirm_input.editable = enabled

func _input(event: InputEvent) -> void:
	if event is InputEventKey and event.keycode == KEY_ENTER and event.pressed:
		_on_register_pressed()
