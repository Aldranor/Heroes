extends Control

@onready var _loading_label: Label = %LoadingLabel
@onready var _version_label: Label = %VersionLabel

func _ready() -> void:
	_version_label.text = "v%s" % ProjectSettings.get_setting("application/config/version", "1.0")
	_loading_label.text = tr("Initialisation...")
	_start_boot_sequence()

func _start_boot_sequence() -> void:
	await get_tree().create_timer(1.0).timeout

	if not AuthService.is_authenticated():
		_loading_label.text = tr("Authentification requise")
		await get_tree().create_timer(0.5).timeout
		SceneManager.go_to_login()
		return

	_loading_label.text = tr("Chargement du profil...")
	var response: ApiResponse = await AuthService.refresh_user()

	if response.success:
		_loading_label.text = tr("Bienvenue !")
		await get_tree().create_timer(0.3).timeout
		SceneManager.go_to_menu()
	else:
		_loading_label.text = tr("Session expiree")
		ApiClient.clear_auth_token()
		await get_tree().create_timer(0.5).timeout
		SceneManager.go_to_login()
