extends CanvasLayer
class_name TransitionService

enum Transition {
	FADE,
	SLIDE_LEFT,
	SLIDE_RIGHT,
	NONE
}

signal transition_halfway

var _color_rect: ColorRect
var _busy: bool = false

func _ready() -> void:
	layer = 128
	_color_rect = ColorRect.new()
	_color_rect.color = Color.BLACK
	_color_rect.mouse_filter = Control.MOUSE_FILTER_STOP
	add_child(_color_rect)

func transition_to(target_scene: String, transition: Transition = Transition.FADE) -> void:
	if _busy:
		return
	_busy = true

	match transition:
		Transition.FADE:
			await _fade_out()
			emit_signal("transition_halfway")
			_change_scene(target_scene)
			await _fade_in()
		Transition.SLIDE_LEFT:
			await _slide_out(1.0)
			emit_signal("transition_halfway")
			_change_scene(target_scene)
			await _slide_in(-1.0)
		Transition.SLIDE_RIGHT:
			await _slide_out(-1.0)
			emit_signal("transition_halfway")
			_change_scene(target_scene)
			await _slide_in(1.0)
		Transition.NONE:
			_change_scene(target_scene)

	_busy = false

func reload(transition: Transition = Transition.FADE) -> void:
	var current: String = _get_current_scene_path()
	if current != "":
		await transition_to(current, transition)

func _fade_out() -> void:
	_color_rect.color = Color(0, 0, 0, 0)
	_color_rect.mouse_filter = Control.MOUSE_FILTER_STOP
	var tw: Tween = create_tween().set_ease(Tween.EASE_IN).set_trans(Tween.TRANS_CUBIC)
	tw.tween_property(_color_rect, "color", Color.BLACK, 0.25)
	await tw.finished

func _fade_in() -> void:
	_color_rect.mouse_filter = Control.MOUSE_FILTER_IGNORE
	var tw: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_CUBIC)
	tw.tween_property(_color_rect, "color", Color(0, 0, 0, 0), 0.25)
	await tw.finished

func _slide_out(dir: float) -> void:
	var size: Vector2 = _color_rect.get_viewport_rect().size
	var tw: Tween = create_tween().set_ease(Tween.EASE_IN).set_trans(Tween.TRANS_CUBIC)
	tw.tween_property(_color_rect, "position", Vector2(-size.x * dir, 0), 0.3)
	await tw.finished

func _slide_in(dir: float) -> void:
	var size: Vector2 = _color_rect.get_viewport_rect().size
	_color_rect.position = Vector2(-size.x * dir, 0)
	var tw: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_CUBIC)
	tw.tween_property(_color_rect, "position", Vector2.ZERO, 0.3)
	await tw.finished

func _change_scene(target: String) -> void:
	get_tree().change_scene_to_file(target)

func _get_current_scene_path() -> String:
	var scene: Node = get_tree().current_scene
	if scene != null and scene.scene_file_path != "":
		return scene.scene_file_path
	return ""
