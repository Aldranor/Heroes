extends Node2D
class_name SpriteAnimator

@export var sheet_texture: Texture2D:
	set(value):
		sheet_texture = value
		if is_node_ready():
			_sprite.texture = sheet_texture

@export var frame_width: int = 80
@export var frame_height: int = 64
@export var row_count: int = 7
@export var col_count: int = 10
@export var animation_states: Dictionary = {
	"idle": {"row": 0, "frames": 4, "fps": 8, "loop": true}
}
@export var current_state: String = "idle"

@onready var _sprite: Sprite2D = %Sprite

var _is_playing: bool = false
var _frame_index: int = 0
var _time_accumulator: float = 0.0

func _ready() -> void:
	_sprite.region_enabled = true
	_sprite.texture = sheet_texture
	_apply_frame_region(current_state, _frame_index)

func _process(delta: float) -> void:
	if not _is_playing:
		return
	if not animation_states.has(current_state):
		return

	var state: Dictionary = animation_states[current_state]
	var fps: float = max(float(state.get("fps", 8.0)), 1.0)
	var frame_count: int = max(int(state.get("frames", 1)), 1)
	var should_loop: bool = bool(state.get("loop", true))
	_time_accumulator += delta
	var frame_time: float = 1.0 / fps

	while _time_accumulator >= frame_time:
		_time_accumulator -= frame_time
		_frame_index += 1
		if _frame_index >= frame_count:
			if should_loop:
				_frame_index = 0
			else:
				_frame_index = frame_count - 1
				_is_playing = false
		_apply_frame_region(current_state, _frame_index)

## Lance une animation d'état.
func play(state_name: String) -> void:
	if not animation_states.has(state_name):
		push_warning("SpriteAnimator.play: état inconnu: %s" % state_name)
		return
	current_state = state_name
	_frame_index = 0
	_time_accumulator = 0.0
	_is_playing = true
	_apply_frame_region(current_state, _frame_index)

## Stoppe l'animation en cours.
func stop() -> void:
	_is_playing = false

## Configure l'orientation gauche/droite.
func set_facing(direction: String) -> void:
	_sprite.flip_h = direction.to_lower() == "left"

func _apply_frame_region(state_name: String, frame_idx: int) -> void:
	if not is_node_ready():
		return
	if not animation_states.has(state_name):
		return
	var state: Dictionary = animation_states[state_name]
	var row: int = clampi(int(state.get("row", 0)), 0, max(row_count - 1, 0))
	var frame_count: int = max(int(state.get("frames", 1)), 1)
	var clamped_frame: int = clampi(frame_idx, 0, frame_count - 1)
	var col: int = clampi(clamped_frame, 0, max(col_count - 1, 0))
	_sprite.region_rect = Rect2(col * frame_width, row * frame_height, frame_width, frame_height)
