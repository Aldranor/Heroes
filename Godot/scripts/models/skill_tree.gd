extends Resource
class_name SkillTree

const SkillNode = preload("res://scripts/models/skill_node.gd")

@export var id: int = 0
@export var slug: String = ""
@export var name: String = ""
@export var subject: String = ""
@export var nodes: Array[SkillNode] = []
@export var skill_points: int = 0
@export var special_points: int = 0
@export var mastery_pct: float = 0.0

func from_dict(d: Dictionary) -> SkillTree:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	name = str(d.get("name", ""))
	subject = str(d.get("subject", ""))
	nodes = []
	var raw_nodes: Variant = d.get("nodes", [])
	if raw_nodes is Array:
		for item: Variant in raw_nodes:
			if item is SkillNode:
				nodes.append(item)
			elif item is Dictionary:
				nodes.append(SkillNode.new().from_dict(item))
	skill_points = int(d.get("skill_points", 0))
	special_points = int(d.get("special_points", 0))
	mastery_pct = float(d.get("mastery_pct", 0.0))
	return self

func to_dict() -> Dictionary:
	var node_data: Array[Dictionary] = []
	for n: SkillNode in nodes:
		node_data.append(n.to_dict())
	return {
		"id": id,
		"slug": slug,
		"name": name,
		"subject": subject,
		"nodes": node_data,
		"skill_points": skill_points,
		"special_points": special_points,
		"mastery_pct": mastery_pct
	}
