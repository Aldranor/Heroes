extends PanelContainer
class_name Toast

enum Type {
	INFO,
	SUCCESS,
	WARNING,
	ERROR
}

const TOAST_SCENE_PATH: String = "res://scenes/shared/ui/toast.tscn"
const TOAST_LAYER_NAME: String = "__toast_layer"
const TOAST_STACK_NAME: String = "__toast_stack"

@export var display_seconds: float = 3.0

@onready var _label: Label = %ToastLabel
@onready var _life_timer: Timer = %LifeTimer

var _fade_tween: Tween

## Affiche un toast depuis n'importe quel script.
static func show_toast(text: String, toast_type: Type = Type.INFO) -> void:
	var scene_tree: SceneTree = Engine.get_main_loop() as SceneTree
	if scene_tree == null:
		return
	var root: Window = scene_tree.root
	if root == null:
		return

	var layer: CanvasLayer = root.get_node_or_null(TOAST_LAYER_NAME)
	if layer == null:
		layer = CanvasLayer.new()
		layer.name = TOAST_LAYER_NAME
		layer.layer = 100
		root.add_child(layer)
		var stack: VBoxContainer = VBoxContainer.new()
		stack.name = TOAST_STACK_NAME
		stack.anchor_left = 0.0
		stack.anchor_top = 0.0
		stack.anchor_right = 1.0
		stack.anchor_bottom = 0.0
		stack.offset_left = 12
		stack.offset_top = 12
		stack.offset_right = -12
		stack.offset_bottom = 0
		stack.alignment = BoxContainer.ALIGNMENT_BEGIN
		stack.add_theme_constant_override("separation", 8)
		layer.add_child(stack)

	var container: VBoxContainer = layer.get_node_or_null(TOAST_STACK_NAME)
	if container == null:
		return
	var toast_scene: PackedScene = load(TOAST_SCENE_PATH) as PackedScene
	if toast_scene == null:
		return
	var toast: Toast = toast_scene.instantiate() as Toast
	container.add_child(toast)
	toast.setup(text, toast_type)

func _ready() -> void:
	_set_alpha(1.0)
	_life_timer.timeout.connect(_on_life_timer_timeout)

## Configure un toast.
func setup(text: String, toast_type: Type) -> void:
	_label.text = text
	_apply_type_color(toast_type)
	_life_timer.start(display_seconds)

func _on_life_timer_timeout() -> void:
	if _fade_tween != null:
		_fade_tween.kill()
	_fade_tween = create_tween()
	var target: CanvasItem = self as CanvasItem
	if target != null:
		_fade_tween.tween_property(target, "modulate:a", 0.0, 0.25)
	_fade_tween.finished.connect(queue_free, CONNECT_ONE_SHOT)

func _apply_type_color(toast_type: Type) -> void:
	var bg: Color = Color("2d2d2d")
	match toast_type:
		Type.INFO:
			bg = Color("2d2d2d")
		Type.SUCCESS:
			bg = Color("2d8a3e")
		Type.WARNING:
			bg = Color("d4af37")
		Type.ERROR:
			bg = Color("b03030")

	var panel: StyleBoxFlat = StyleBoxFlat.new()
	panel.bg_color = bg
	panel.border_color = Color("4a4540")
	panel.border_width_left = 2
	panel.border_width_top = 2
	panel.border_width_right = 2
	panel.border_width_bottom = 2
	panel.content_margin_left = 8
	panel.content_margin_top = 8
	panel.content_margin_right = 8
	panel.content_margin_bottom = 8
	var container: PanelContainer = self as PanelContainer
	if container != null:
		container.add_theme_stylebox_override("panel", panel)

func _set_alpha(alpha: float) -> void:
	var item: CanvasItem = self as CanvasItem
	if item != null:
		item.modulate.a = alpha
