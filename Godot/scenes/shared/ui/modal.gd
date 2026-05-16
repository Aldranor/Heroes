extends CanvasLayer
class_name Modal

signal confirmed()
signal cancelled()
signal resolved(result: bool)

const MODAL_SCENE_PATH: String = "res://scenes/shared/ui/modal.tscn"

@onready var _panel: PanelContainer = %Panel
@onready var _title: Label = %Title
@onready var _message: Label = %Message
@onready var _confirm_button: Button = %ConfirmButton
@onready var _cancel_button: Button = %CancelButton

## Ouvre une modal et renvoie true/false (à await).
static func confirm(title: String, message: String) -> bool:
	var tree: SceneTree = Engine.get_main_loop() as SceneTree
	if tree == null or tree.root == null:
		return false
	var modal_scene: PackedScene = load(MODAL_SCENE_PATH) as PackedScene
	if modal_scene == null:
		return false
	var modal: Modal = modal_scene.instantiate() as Modal
	tree.root.add_child(modal)
	modal.setup(title, message)
	var result: Variant = await modal.resolved
	if result is Array and result.size() > 0:
		return bool(result[0])
	return bool(result)

func _ready() -> void:
	layer = 95
	if _confirm_button.has_method("set"):
		_confirm_button.set("button_type", 0)
	if _cancel_button.has_method("set"):
		_cancel_button.set("button_type", 1)
	_confirm_button.text = "Confirmer"
	_cancel_button.text = "Annuler"
	_confirm_button.pressed.connect(_on_confirm_pressed)
	_cancel_button.pressed.connect(_on_cancel_pressed)
	_play_appear_animation()

## Configure le contenu de la modal.
func setup(title_text: String, message_text: String) -> void:
	_title.text = title_text
	_message.text = message_text

func _on_confirm_pressed() -> void:
	emit_signal("confirmed")
	emit_signal("resolved", true)
	queue_free()

func _on_cancel_pressed() -> void:
	emit_signal("cancelled")
	emit_signal("resolved", false)
	queue_free()

func _play_appear_animation() -> void:
	var start_y: float = _panel.position.y + 36.0
	var end_y: float = _panel.position.y
	_panel.position.y = start_y
	var tween: Tween = create_tween().set_trans(Tween.TRANS_CUBIC).set_ease(Tween.EASE_OUT)
	tween.tween_property(_panel, "position:y", end_y, 0.2)
