extends Control

const STEPS: Array[String] = [
	"res://scenes/onboarding/steps/step_welcome.tscn",
	"res://scenes/onboarding/steps/step_avatar_body.tscn",
	"res://scenes/onboarding/steps/step_avatar_hair.tscn",
	"res://scenes/onboarding/steps/step_avatar_outfit.tscn",
	"res://scenes/onboarding/steps/step_companion.tscn",
	"res://scenes/onboarding/steps/step_tutorial.tscn",
]

const MOCK = preload("res://mock/avatar_options.gd")

var current_step: int = 0
var selected_body_key: String = ""
var selected_hair_key: String = ""
var selected_outfit_key: String = ""
var selected_companion_slug: String = ""
var skin_color: Color = Color.WHITE
var hair_color: Color = Color(0.2, 0.2, 0.2, 1)

var _current_step_node: Control = null
var _is_transitioning: bool = false
var _progress_dots: Array[ColorRect] = []

@onready var _step_container: Control = %StepContainer
@onready var _progress_container: HBoxContainer = %ProgressContainer
@onready var _skip_button: Button = %SkipButton
@onready var _bottom_bar: HBoxContainer = %BottomBar
@onready var _back_button: Button = %BackButton
@onready var _next_button: Button = %NextButton
@onready var _loading_overlay: Control = %LoadingOverlay

func _ready() -> void:
	_build_progress_dots()
	_skip_button.pressed.connect(_on_skip_pressed)
	_back_button.pressed.connect(_on_back_pressed)
	_next_button.pressed.connect(_on_next_pressed)
	_restore_state()
	_load_step(0)

func _build_progress_dots() -> void:
	var dot_count: int = STEPS.size() - 1
	for i: int in range(dot_count):
		var dot: ColorRect = ColorRect.new()
		dot.custom_minimum_size = Vector2(10, 10)
		dot.color = DesignTokens.Colors.PanelTokens.Dark.BORDER
		dot.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		_progress_container.add_child(dot)
		_progress_dots.append(dot)

func _restore_state() -> void:
	var saved_step: int = OnboardingService.get_saved_step()
	if saved_step > 0 and saved_step < STEPS.size():
		current_step = saved_step
	var avatar_state: Dictionary = OnboardingService.get_avatar_state()
	if not avatar_state.is_empty():
		selected_body_key = str(avatar_state.get("selected_body_key", ""))
		selected_hair_key = str(avatar_state.get("selected_hair_key", ""))
		selected_outfit_key = str(avatar_state.get("selected_outfit_key", ""))
		if avatar_state.has("skin_color") and avatar_state["skin_color"] is Color:
			skin_color = avatar_state["skin_color"]
		if avatar_state.has("hair_color") and avatar_state["hair_color"] is Color:
			hair_color = avatar_state["hair_color"]
	var companion_slug: String = OnboardingService.get_companion_slug()
	if companion_slug != "":
		selected_companion_slug = companion_slug

func _load_step(index: int) -> void:
	if index < 0 or index >= STEPS.size():
		return
	if _is_transitioning:
		return
	_is_transitioning = true

	if _current_step_node != null:
		_current_step_node.queue_free()
		_current_step_node = null

	var path: String = STEPS[index]
	var scene: PackedScene = load(path)
	var step: Node = scene.instantiate()
	_step_container.add_child(step)
	_current_step_node = step

	if step.has_method("set_data"):
		var data: Dictionary = _build_step_data(index)
		step.set_data(data)

	if step.has_signal("data_changed"):
		step.data_changed.connect(_on_step_data_changed)
	if step.has_signal("step_next"):
		step.step_next.connect(_on_auto_next)
	if step.has_signal("tutorial_complete"):
		step.tutorial_complete.connect(_on_tutorial_complete)

	_update_ui()
	_is_transitioning = false

func _build_step_data(step_index: int) -> Dictionary:
	var data: Dictionary = {
		"selected_body_key": selected_body_key,
		"selected_hair_key": selected_hair_key,
		"selected_outfit_key": selected_outfit_key,
		"selected_companion_slug": selected_companion_slug,
		"skin_color": skin_color,
		"hair_color": hair_color,
	}
	return data

func _update_ui() -> void:
	_update_progress()
	_update_nav_buttons()

func _update_progress() -> void:
	var show_progress: bool = current_step > 0 and current_step < STEPS.size()
	_progress_container.visible = show_progress
	for i: int in range(_progress_dots.size()):
		var step_index: int = i + 1
		var dot: ColorRect = _progress_dots[i]
		if step_index <= current_step:
			dot.color = DesignTokens.Colors.Accent.PRIMARY
		else:
			dot.color = DesignTokens.Colors.PanelTokens.Dark.BORDER

func _update_nav_buttons() -> void:
	var is_welcome: bool = current_step == 0
	var is_tutorial: bool = current_step >= STEPS.size() - 1
	var show_flow_nav: bool = not is_welcome and not is_tutorial
	_bottom_bar.visible = show_flow_nav
	_skip_button.visible = current_step > 0
	_skip_button.text = "Passer le tutoriel" if is_tutorial else "Passer la personnalisation"
	_back_button.visible = current_step > 0 and not is_welcome
	_next_button.text = "Commencer l'aventure!" if is_tutorial else "Suivant"
	_step_container.offset_top = 48.0 if current_step > 0 else 0.0
	_step_container.offset_bottom = -56.0 if show_flow_nav else 0.0

func _on_skip_pressed() -> void:
	AudioManager.play_sfx("click", -5.0)
	_apply_defaults(true)
	_complete_onboarding()

func _on_back_pressed() -> void:
	if current_step > 0:
		AudioManager.play_sfx("click", -5.0)
		current_step -= 1
		_persist_state()
		_load_step(current_step)

func _on_next_pressed() -> void:
	AudioManager.play_sfx("click", -5.0)
	_save_current_step_data()

	if current_step >= STEPS.size() - 1:
		_complete_onboarding()
	else:
		current_step += 1
		_persist_state()
		_load_step(current_step)

func _on_auto_next() -> void:
	_on_next_pressed()

func _on_tutorial_complete() -> void:
	_save_current_step_data()
	_complete_onboarding()

func _on_step_data_changed(data: Dictionary) -> void:
	if data.has("selected_body_key"):
		selected_body_key = str(data["selected_body_key"])
	if data.has("selected_hair_key"):
		selected_hair_key = str(data["selected_hair_key"])
	if data.has("selected_outfit_key"):
		selected_outfit_key = str(data["selected_outfit_key"])
	if data.has("selected_companion_slug"):
		selected_companion_slug = str(data["selected_companion_slug"])
	if data.has("skin_color") and data["skin_color"] is Color:
		skin_color = data["skin_color"]
	if data.has("hair_color") and data["hair_color"] is Color:
		hair_color = data["hair_color"]
	_persist_state()

func _save_current_step_data() -> void:
	if _current_step_node != null and _current_step_node.has_method("get_data"):
		var data: Dictionary = _current_step_node.get_data()
		_on_step_data_changed(data)

func _persist_state() -> void:
	OnboardingService.save_step(current_step)
	var avatar_data: Dictionary = {
		"selected_body_key": selected_body_key,
		"selected_hair_key": selected_hair_key,
		"selected_outfit_key": selected_outfit_key,
		"skin_color": skin_color,
		"hair_color": hair_color,
	}
	OnboardingService.save_avatar_state(avatar_data)
	if selected_companion_slug != "":
		OnboardingService.save_companion_slug(selected_companion_slug)

func _apply_defaults(force_all: bool = false) -> void:
	if selected_body_key == "" or force_all:
		selected_body_key = MOCK.get_default_body_key()
	if selected_hair_key == "" or force_all:
		selected_hair_key = MOCK.get_default_hair_key()
	if selected_outfit_key == "" or force_all:
		selected_outfit_key = MOCK.get_default_outfit_key()
	if selected_companion_slug == "" or force_all:
		selected_companion_slug = MOCK.get_default_companion_slug()
	if force_all:
		skin_color = MOCK.get_default_skin_color()
		hair_color = MOCK.get_default_hair_color()
	_persist_state()

func _complete_onboarding() -> void:
	_apply_defaults()
	_loading_overlay.visible = true

	var avatar_payload: Dictionary = {
		"body_key": selected_body_key,
		"hair_key": selected_hair_key,
		"outfit_preset_key": selected_outfit_key,
		"skin_color": skin_color.to_html(true),
		"hair_color": hair_color.to_html(true),
	}

	var complete_data: Dictionary = {
		"avatar": avatar_payload,
		"companion_slug": selected_companion_slug,
	}

	var response: ApiResponse = await OnboardingService.complete_onboarding(complete_data)
	_loading_overlay.visible = false

	if response.success:
		SceneManager.change_scene(SceneManager.DASHBOARD, SceneManager.TransitionStyle.FADE)
	else:
		_show_error("Erreur lors de la finalisation : %s" % str(response.data))

func _show_error(message: String) -> void:
	push_error(message)
	if has_node("%AppToast"):
		%AppToast.show_toast(message, 0)
