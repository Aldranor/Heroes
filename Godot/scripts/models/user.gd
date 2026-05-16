extends Resource
class_name User

@export var id: int = 0
@export var email: String = ""
@export var name: String = ""
@export var level: int = 1
@export var xp: int = 0
@export var xp_to_next_level: int = 100
@export var coins: int = 0
@export var current_difficulty: int = 1
@export var avatar: AvatarData = AvatarData.new()
@export var current_node_id: int = 0
@export var streak_days: int = 0

## Hydrate la resource User depuis un dictionnaire.
func from_dict(d: Dictionary) -> User:
	id = int(d.get("id", 0))
	email = str(d.get("email", ""))
	name = str(d.get("name", ""))
	level = int(d.get("level", 1))
	xp = int(d.get("xp", 0))
	xp_to_next_level = int(d.get("xp_to_next_level", 100))
	coins = int(d.get("coins", 0))
	current_difficulty = int(d.get("current_difficulty", 1))
	current_node_id = int(d.get("current_node_id", 0))
	streak_days = int(d.get("streak_days", 0))

	var avatar_data: Variant = d.get("avatar", {})
	if avatar_data is AvatarData:
		avatar = avatar_data
	elif avatar_data is Dictionary:
		avatar = AvatarData.new().from_dict(avatar_data)
	else:
		avatar = AvatarData.new()
	return self

## Convertit le User vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"email": email,
		"name": name,
		"level": level,
		"xp": xp,
		"xp_to_next_level": xp_to_next_level,
		"coins": coins,
		"current_difficulty": current_difficulty,
		"avatar": avatar.to_dict() if avatar != null else {},
		"current_node_id": current_node_id,
		"streak_days": streak_days
	}
