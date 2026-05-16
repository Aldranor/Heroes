extends Node

var enabled: bool = false

func _ready() -> void:
	var cfg: ConfigFile = ConfigFile.new()
	if cfg.load("res://config/env.cfg") == OK:
		enabled = cfg.get_value("debug", "mock_mode", false)
	else:
		enabled = false
