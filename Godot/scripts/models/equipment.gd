extends Resource
class_name Equipment

@export var id: int = 0
@export var slug: String = ""
@export var name: String = ""
@export var type: String = ""
@export var rarity: String = ""
@export var sprite_path: String = ""
@export var sprite_slot: String = ""
@export var stat_bonus: Dictionary = {}

## Hydrate la resource Equipment depuis un dictionnaire.
func from_dict(d: Dictionary) -> Equipment:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	type = str(d.get("type", ""))
	rarity = str(d.get("rarity", ""))
	sprite_path = str(d.get("sprite_path", ""))
	sprite_slot = str(d.get("sprite_slot", ""))
	stat_bonus = d.get("stat_bonus", {}).duplicate(true)
	return self

## Convertit la resource Equipment vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"slug": slug,
		"name": name,
		"type": type,
		"rarity": rarity,
		"sprite_path": sprite_path,
		"sprite_slot": sprite_slot,
		"stat_bonus": stat_bonus.duplicate(true)
	}
