extends Resource
class_name Companion

@export var id: int = 0
@export var slug: String = ""
@export var name: String = ""
@export var description: String = ""
@export var rarity: String = ""
@export var category: String = ""
@export var sprite_path: String = ""
@export var base_hp: int = 0
@export var base_attack: int = 0
@export var base_defense: int = 0
@export var base_speed: int = 0

## Hydrate la resource Companion depuis un dictionnaire.
func from_dict(d: Dictionary) -> Companion:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	description = str(d.get("description", ""))
	rarity = str(d.get("rarity", ""))
	category = str(d.get("category", ""))
	sprite_path = str(d.get("sprite_path", ""))
	base_hp = int(d.get("base_hp", 0))
	base_attack = int(d.get("base_attack", 0))
	base_defense = int(d.get("base_defense", 0))
	base_speed = int(d.get("base_speed", 0))
	return self

## Convertit la resource Companion vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"slug": slug,
		"name": name,
		"description": description,
		"rarity": rarity,
		"category": category,
		"sprite_path": sprite_path,
		"base_hp": base_hp,
		"base_attack": base_attack,
		"base_defense": base_defense,
		"base_speed": base_speed
	}
