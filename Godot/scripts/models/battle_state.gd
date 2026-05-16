extends Resource
class_name BattleState

@export var battle_id: int = 0
@export var enemy_id: int = 0
@export var turn_index: int = 0
@export var is_active: bool = false
@export var started_at: String = ""

## Hydrate la resource BattleState depuis un dictionnaire.
func from_dict(d: Dictionary) -> BattleState:
	battle_id = int(d.get("battle_id", 0))
	enemy_id = int(d.get("enemy_id", 0))
	turn_index = int(d.get("turn_index", 0))
	is_active = bool(d.get("is_active", false))
	started_at = str(d.get("started_at", ""))
	return self

## Convertit la resource BattleState vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"battle_id": battle_id,
		"enemy_id": enemy_id,
		"turn_index": turn_index,
		"is_active": is_active,
		"started_at": started_at
	}
