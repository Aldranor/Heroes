extends Resource
class_name GameSettings

@export var music_volume: float = 0.7
@export var sfx_volume: float = 0.9
@export var locale: String = "fr"
@export var reduce_motion: bool = false
@export var notifications_enabled: bool = true

## Hydrate la resource GameSettings depuis un dictionnaire.
func from_dict(d: Dictionary) -> GameSettings:
	music_volume = float(d.get("music_volume", 0.7))
	sfx_volume = float(d.get("sfx_volume", 0.9))
	locale = str(d.get("locale", "fr"))
	reduce_motion = bool(d.get("reduce_motion", false))
	notifications_enabled = bool(d.get("notifications_enabled", true))
	return self

## Convertit la resource GameSettings vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"music_volume": music_volume,
		"sfx_volume": sfx_volume,
		"locale": locale,
		"reduce_motion": reduce_motion,
		"notifications_enabled": notifications_enabled
	}
