extends Resource
class_name AvatarData

@export var body_key: String = ""
@export var hair_key: String = ""
@export var outfit_preset_key: String = ""
@export var skin_color: Color = Color(1, 1, 1, 1)
@export var hair_color: Color = Color(0.2, 0.2, 0.2, 1)
@export var accent_color: Color = Color(0.3, 0.7, 1.0, 1)
@export var equipped_items: Dictionary = {}

## Hydrate la resource depuis un dictionnaire API/cache.
func from_dict(d: Dictionary) -> AvatarData:
	body_key = str(d.get("body_key", ""))
	hair_key = str(d.get("hair_key", ""))
	outfit_preset_key = str(d.get("outfit_preset_key", ""))
	skin_color = _color_from_variant(d.get("skin_color", skin_color), skin_color)
	hair_color = _color_from_variant(d.get("hair_color", hair_color), hair_color)
	accent_color = _color_from_variant(d.get("accent_color", accent_color), accent_color)
	equipped_items = d.get("equipped_items", {}).duplicate(true)
	return self

## Convertit la resource vers un dictionnaire serialisable JSON.
func to_dict() -> Dictionary:
	return {
		"body_key": body_key,
		"hair_key": hair_key,
		"outfit_preset_key": outfit_preset_key,
		"skin_color": skin_color.to_html(true),
		"hair_color": hair_color.to_html(true),
		"accent_color": accent_color.to_html(true),
		"equipped_items": equipped_items.duplicate(true)
	}

func _color_from_variant(value: Variant, fallback: Color) -> Color:
	if value is Color:
		return value
	if value is String:
		return Color.from_string(value, fallback)
	if value is Dictionary:
		var data: Dictionary = value
		return Color(
			float(data.get("r", fallback.r)),
			float(data.get("g", fallback.g)),
			float(data.get("b", fallback.b)),
			float(data.get("a", fallback.a))
		)
	return fallback
