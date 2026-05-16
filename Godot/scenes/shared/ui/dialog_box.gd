extends PanelContainer
class_name DialogBox

signal dialog_finished()

@export var chars_per_second: float = 45.0

@onready var _avatar: TextureRect = %SpeakerAvatar
@onready var _speaker_name: Label = %SpeakerName
@onready var _dialog_text: RichTextLabel = %DialogText
@onready var _next_button: Button = %NextButton
@onready var _next_indicator: Label = %NextIndicator
@onready var _type_timer: Timer = %TypeTimer
@onready var _blink_timer: Timer = %BlinkTimer

var _full_text: String = ""
var _visible_count: int = 0
var _is_typing: bool = false

func _ready() -> void:
	_next_button.text = "Suivant"
	_next_button.pressed.connect(_on_next_pressed)
	_type_timer.timeout.connect(_on_type_timer_timeout)
	_blink_timer.timeout.connect(_on_blink_timer_timeout)
	_next_indicator.text = ">>"
	_next_indicator.visible = false
	_dialog_text.bbcode_enabled = false
	_dialog_text.scroll_active = false
	set_dialog("", "", null)

## Configure la boîte de dialogue.
func set_dialog(speaker_name: String, text: String, avatar_texture: Texture2D) -> void:
	_speaker_name.text = speaker_name
	_avatar.texture = avatar_texture
	_full_text = text
	_visible_count = 0
	_dialog_text.text = ""
	_next_indicator.visible = false
	_start_typing()

func _start_typing() -> void:
	_is_typing = true
	var safe_speed: float = max(chars_per_second, 1.0)
	_type_timer.wait_time = 1.0 / safe_speed
	_type_timer.start()

func _finish_typing() -> void:
	_is_typing = false
	_type_timer.stop()
	_dialog_text.text = _full_text
	_next_indicator.visible = true
	if _blink_timer.is_stopped():
		_blink_timer.start()

func _on_next_pressed() -> void:
	if _is_typing:
		_finish_typing()
		return
	emit_signal("dialog_finished")

func _on_type_timer_timeout() -> void:
	if not _is_typing:
		return
	_visible_count += 1
	_dialog_text.text = _full_text.substr(0, _visible_count)
	if _visible_count >= _full_text.length():
		_finish_typing()

func _on_blink_timer_timeout() -> void:
	_next_indicator.visible = not _next_indicator.visible
