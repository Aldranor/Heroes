extends Control

@onready var _card: PanelContainer = %Card
@onready var _email_input: LineEdit = %EmailInput
@onready var _password_input: LineEdit = %PasswordInput
@onready var _login_button: AppButton = %LoginButton
@onready var _forgot_link: Button = %ForgotLink
@onready var _register_link: Button = %RegisterLink
@onready var _remember_check: CheckBox = %RememberCheck
@onready var _error_label: Label = %ErrorLabel

func _ready() -> void:
	_style_background()
	_style_card()

	_login_button.pressed.connect(_on_login_pressed)
	_forgot_link.pressed.connect(_on_forgot_pressed)
	_register_link.pressed.connect(_on_register_pressed)

	_error_label.hide()

func _style_background() -> void:
	var bg := ColorRect.new()
	bg.name = "_bg"
	bg.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	bg.mouse_filter = Control.MOUSE_FILTER_IGNORE
	bg.color = Color(24.0 / 255.0, 22.0 / 255.0, 20.0 / 255.0, 1)
	add_child(bg)
	move_child(bg, 0)

	var grid := ColorRect.new()
	grid.name = "_grid"
	grid.set_anchors_and_offsets_preset(Control.PRESET_FULL_RECT)
	grid.mouse_filter = Control.MOUSE_FILTER_IGNORE
	grid.color = Color(1, 1, 1, 0.01)
	add_child(grid)
	move_child(grid, 1)

func _style_card() -> void:
	var s := _card.get_theme_stylebox("panel", "CardPanel")
	if s == null:
		return
	s = s.duplicate() as StyleBoxFlat
	if s == null:
		return
	s.shadow_size = 30
	s.shadow_color = Color(0, 0, 0, 0.22)
	s.shadow_offset = Vector2(0, 12)
	_card.add_theme_stylebox_override("panel", s)

func _show_error(msg: String) -> void:
	_error_label.text = msg
	_error_label.add_theme_color_override("font_color", Color("fca5a5"))
	_error_label.show()
	_email_input.add_theme_color_override("font_color", Color("fca5a5"))

func _clear_error() -> void:
	_error_label.hide()
	_email_input.remove_theme_color_override("font_color")

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
	_email_input.editable = enabled
	_password_input.editable = enabled

func _input(event: InputEvent) -> void:
	if event is InputEventKey and event.keycode == KEY_ENTER and event.pressed:
		_on_login_pressed()
