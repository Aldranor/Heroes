extends Node

@onready var _blocker: Control = %Blocker
@onready var _message_label: Label = %MessageLabel
@onready var _spinner_label: Label = %SpinnerLabel
@onready var _spinner_timer: Timer = %SpinnerTimer

var _spinner_frames: PackedStringArray = PackedStringArray(["[ ]", "[=]", "[==]", "[===]"])
var _spinner_index: int = 0

func _ready() -> void:
	set("layer", 90)
	_set_layer_visible(false)
	_blocker.mouse_filter = Control.MOUSE_FILTER_STOP
	_spinner_timer.timeout.connect(_on_spinner_timer_timeout)

## Affiche l'overlay de chargement.
func show_loading(message: String = "Chargement...") -> void:
	_message_label.text = message
	_spinner_index = 0
	_spinner_label.text = _spinner_frames[_spinner_index]
	_set_layer_visible(true)
	_spinner_timer.start()

## Cache l'overlay.
func hide_loading() -> void:
	_spinner_timer.stop()
	_set_layer_visible(false)

## Alias ergonomique singleton.
func show(message: String = "Chargement...") -> void:
	show_loading(message)

## Alias ergonomique singleton.
func hide() -> void:
	hide_loading()

func _on_spinner_timer_timeout() -> void:
	_spinner_index = (_spinner_index + 1) % _spinner_frames.size()
	_spinner_label.text = _spinner_frames[_spinner_index]

func _set_layer_visible(is_visible: bool) -> void:
	set("visible", is_visible)
