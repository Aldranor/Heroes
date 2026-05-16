extends Resource
class_name Skill

@export var slug: String = ""
@export var name: String = ""
@export var description: String = ""
@export var type: String = ""
@export var power: int = 0
@export var mana_cost: int = 0
@export var cooldown: int = 0
@export var target: String = ""
@export var animation: String = ""

## Hydrate la resource Skill depuis un dictionnaire.
func from_dict(d: Dictionary) -> Skill:
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	description = str(d.get("description", ""))
	type = str(d.get("type", ""))
	power = int(d.get("power", 0))
	mana_cost = int(d.get("mana_cost", 0))
	cooldown = int(d.get("cooldown", 0))
	target = str(d.get("target", ""))
	animation = str(d.get("animation", ""))
	return self

## Convertit la resource Skill vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"slug": slug,
		"name": name,
		"description": description,
		"type": type,
		"power": power,
		"mana_cost": mana_cost,
		"cooldown": cooldown,
		"target": target,
		"animation": animation
	}
