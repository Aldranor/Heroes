extends Resource
class_name UserCompanion

@export var id: int = 0
@export var companion: Companion = Companion.new()
@export var level: int = 1
@export var xp: int = 0
@export var affinity: int = 0
@export var is_active: bool = false
@export var slot_position: int = 0
@export var equipped_items: Array[Equipment] = []

## Hydrate la resource UserCompanion depuis un dictionnaire.
func from_dict(d: Dictionary) -> UserCompanion:
	id = int(d.get("id", 0))
	level = int(d.get("level", 1))
	xp = int(d.get("xp", 0))
	affinity = int(d.get("affinity", 0))
	is_active = bool(d.get("is_active", false))
	slot_position = int(d.get("slot_position", 0))

	var companion_data: Variant = d.get("companion", {})
	if companion_data is Companion:
		companion = companion_data
	elif companion_data is Dictionary:
		companion = Companion.new().from_dict(companion_data)
	else:
		companion = Companion.new()

	equipped_items = []
	var raw_items: Variant = d.get("equipped_items", [])
	if raw_items is Array:
		for item: Variant in raw_items:
			if item is Equipment:
				equipped_items.append(item)
			elif item is Dictionary:
				equipped_items.append(Equipment.new().from_dict(item))
	return self

## Convertit la resource UserCompanion vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	var equipped_items_data: Array[Dictionary] = []
	for item: Equipment in equipped_items:
		equipped_items_data.append(item.to_dict())
	return {
		"id": id,
		"companion": companion.to_dict() if companion != null else {},
		"level": level,
		"xp": xp,
		"affinity": affinity,
		"is_active": is_active,
		"slot_position": slot_position,
		"equipped_items": equipped_items_data
	}
