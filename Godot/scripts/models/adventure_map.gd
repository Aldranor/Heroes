extends Resource
class_name AdventureMap

const AdventureNode = preload("res://scripts/models/adventure_node.gd")

@export var id: int = 0
@export var world_name: String = ""
@export var zone_name: String = ""
@export var nodes: Array[AdventureNode] = []
@export var paths: Array[Dictionary] = []
@export var current_node_id: int = 0
@export var dialogue_scene: Dictionary = {}

func from_dict(d: Dictionary) -> AdventureMap:
	id = int(d.get("id", 0))
	world_name = str(d.get("world_name", ""))
	zone_name = str(d.get("zone_name", ""))
	nodes = []
	var raw_nodes: Variant = d.get("nodes", [])
	if raw_nodes is Array:
		for item: Variant in raw_nodes:
			if item is AdventureNode:
				nodes.append(item)
			elif item is Dictionary:
				nodes.append(AdventureNode.new().from_dict(item))
	paths = []
	var raw_paths: Variant = d.get("paths", [])
	if raw_paths is Array:
		for item: Variant in raw_paths:
			if item is Dictionary and item.has("from_id") and item.has("to_id"):
				paths.append({
					"from_id": int(item.get("from_id", 0)),
					"to_id": int(item.get("to_id", 0))
				})
	current_node_id = int(d.get("current_node_id", 0))
	var raw_dialogue: Variant = d.get("dialogue_scene", {})
	if raw_dialogue is Dictionary and not raw_dialogue.is_empty():
		dialogue_scene = raw_dialogue
	return self

func to_dict() -> Dictionary:
	var node_data: Array[Dictionary] = []
	for n: AdventureNode in nodes:
		node_data.append(n.to_dict())
	return {
		"id": id,
		"world_name": world_name,
		"zone_name": zone_name,
		"nodes": node_data,
		"paths": paths,
		"current_node_id": current_node_id,
		"dialogue_scene": dialogue_scene
	}
