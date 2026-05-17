extends Resource
class_name SkillNode

@export var id: int = 0
@export var slug: String = ""
@export var name: String = ""
@export var description: String = ""
@export var tree_id: int = 0
@export var row: int = 0
@export var col: int = 0
@export var type: String = ""
@export var effects: Array[Dictionary] = []
@export var prerequisites: Array[String] = []
@export var cost: int = 0
@export var special_cost: int = 0
@export var status: String = "locked"
@export var icon_path: String = ""

func from_dict(d: Dictionary) -> SkillNode:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	description = str(d.get("description", ""))
	tree_id = int(d.get("tree_id", 0))
	row = int(d.get("row", 0))
	col = int(d.get("col", 0))
	type = str(d.get("type", ""))
	effects = []
	var raw_effects: Variant = d.get("effects", [])
	if raw_effects is Array:
		for item: Variant in raw_effects:
			if item is Dictionary:
				effects.append({
					"label": str(item.get("label", "")),
					"value": item.get("value", 0)
				})
	prerequisites = []
	var raw_prereqs: Variant = d.get("prerequisites", [])
	if raw_prereqs is Array:
		for item: Variant in raw_prereqs:
			prerequisites.append(str(item))
	cost = int(d.get("cost", 0))
	special_cost = int(d.get("special_cost", 0))
	status = str(d.get("status", "locked"))
	icon_path = str(d.get("icon_path", ""))
	return self

func to_dict() -> Dictionary:
	var effect_data: Array[Dictionary] = []
	for e: Dictionary in effects:
		effect_data.append({
			"label": e.get("label", ""),
			"value": e.get("value", 0)
		})
	return {
		"id": id,
		"slug": slug,
		"name": name,
		"description": description,
		"tree_id": tree_id,
		"row": row,
		"col": col,
		"type": type,
		"effects": effect_data,
		"prerequisites": prerequisites,
		"cost": cost,
		"special_cost": special_cost,
		"status": status,
		"icon_path": icon_path
	}
