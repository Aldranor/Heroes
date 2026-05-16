extends Control

@onready var _launch_button: Button = %LaunchButton
@onready var _reset_button: Button = %ResetButton
@onready var _status_label: Label = %StatusLabel
@onready var _debug_log: RichTextLabel = %DebugLog

const ONBOARDING_PATH: String = "res://scenes/onboarding/onboarding_flow.tscn"

func _ready() -> void:
	_launch_button.pressed.connect(_on_launch_pressed)
	_reset_button.pressed.connect(_on_reset_pressed)
	_refresh_status()

func _on_launch_pressed() -> void:
	MockMode.enabled = true
	_log("MockMode activé, lancement de l'onboarding...")
	SceneManager.change_scene(ONBOARDING_PATH, SceneManager.TransitionStyle.NONE)

func _on_reset_pressed() -> void:
	OnboardingService.reset()
	_log("État d'onboarding réinitialisé")
	_refresh_status()

func _refresh_status() -> void:
	var state: Dictionary = OnboardingService.get_state()
	_status_label.text = "État :\n"
	_status_label.text += "  onboarded: %s\n" % str(state.get("is_onboarded", false))
	_status_label.text += "  saved_step: %s\n" % str(state.get("saved_step", 0))
	_status_label.text += "  avatar_state: %s\n" % str(state.get("avatar_state", {}))
	_status_label.text += "  companion: %s\n" % str(state.get("companion_slug", ""))

func _log(msg: String) -> void:
	_debug_log.text += "[%s] %s\n" % [Time.get_time_string_from_system(), msg]
