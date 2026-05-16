extends ColorRect
class_name AppSceneTransition

signal transition_finished

func _ready() -> void:
	color = Color(10.0 / 255.0, 15.0 / 255.0, 20.0 / 255.0, 0.0)
	mouse_filter = Control.MOUSE_FILTER_IGNORE

func fade_in(duration: float = 0.4) -> void:
	color.a = 0.0
	var t := create_tween().set_ease(Tween.EASE_IN_OUT).set_trans(Tween.TRANS_CUBIC)
	t.tween_property(self, "color:a", 1.0, duration)
	await t.finished
	transition_finished.emit()

func fade_out(duration: float = 0.4) -> void:
	var t := create_tween().set_ease(Tween.EASE_IN_OUT).set_trans(Tween.TRANS_CUBIC)
	t.tween_property(self, "color:a", 0.0, duration)
	await t.finished
	transition_finished.emit()
