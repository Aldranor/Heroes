extends Resource
class_name AdventureNode

@export var id: int = 0
@export var slug: String = ""
@export var name: String = ""
@export var node_type: String = ""
@export var position_x: float = 0.0
@export var position_y: float = 0.0
@export var status: String = "locked"
@export var enemy_id: int = 0
@export var lesson_id: int = 0

## Hydrate la resource AdventureNode depuis un dictionnaire.
func from_dict(d: Dictionary) -> AdventureNode:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	node_type = str(d.get("node_type", ""))
	position_x = float(d.get("position_x", 0.0))
	position_y = float(d.get("position_y", 0.0))
	status = str(d.get("status", "locked"))
	enemy_id = int(d.get("enemy_id", 0))
	lesson_id = int(d.get("lesson_id", 0))
	return self

## Convertit la resource AdventureNode vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"slug": slug,
		"name": name,
		"node_type": node_type,
		"position_x": position_x,
		"position_y": position_y,
		"status": status,
		"enemy_id": enemy_id,
		"lesson_id": lesson_id
	}
