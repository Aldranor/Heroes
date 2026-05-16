extends Node

enum TransitionStyle {
	FADE,
	SLIDE_LEFT,
	SLIDE_RIGHT,
	NONE
}

const MAIN_MENU: String = "res://scenes/menu/main_menu.tscn"
const LOGIN: String = "res://scenes/auth/login.tscn"
const REGISTER: String = "res://scenes/auth/register.tscn"
const SETTINGS: String = "res://scenes/menu/settings.tscn"
const DASHBOARD: String = "res://scenes/dashboard/dashboard.tscn"
const ONBOARDING: String = "res://scenes/onboarding/onboarding_flow.tscn"

var _transition: TransitionService = null
var _modal_stack: Array[Node] = []

func _ready() -> void:
	_transition = TransitionService.new()
	add_child(_transition)

func change_scene(scene_path: String, transition: TransitionStyle = TransitionStyle.FADE) -> void:
	var godot_transition: TransitionService.Transition = _to_godot_transition(transition)
	_transition.transition_to(scene_path, godot_transition)

func reload_current(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	var godot_transition: TransitionService.Transition = _to_godot_transition(transition)
	_transition.reload(godot_transition)

func push_modal(scene_path: String) -> Node:
	var modal: Node = load(scene_path).instantiate()
	get_tree().root.add_child(modal)
	_modal_stack.append(modal)
	return modal

func pop_modal() -> void:
	if _modal_stack.is_empty():
		return
	var modal: Node = _modal_stack.pop_back()
	modal.queue_free()

func go_to_login(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(LOGIN, transition)

func go_to_menu(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(MAIN_MENU, transition)

func go_to_register(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(REGISTER, transition)

func go_to_settings(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(SETTINGS, transition)

func go_to_dashboard(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(DASHBOARD, transition)

func go_to_onboarding(transition: TransitionStyle = TransitionStyle.FADE) -> void:
	change_scene(ONBOARDING, transition)

func _to_godot_transition(style: TransitionStyle) -> TransitionService.Transition:
	match style:
		TransitionStyle.FADE:
			return TransitionService.Transition.FADE
		TransitionStyle.SLIDE_LEFT:
			return TransitionService.Transition.SLIDE_LEFT
		TransitionStyle.SLIDE_RIGHT:
			return TransitionService.Transition.SLIDE_RIGHT
		TransitionStyle.NONE:
			return TransitionService.Transition.NONE
		_:
			return TransitionService.Transition.FADE
