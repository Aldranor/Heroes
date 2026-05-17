extends Resource
class_name BattleBuild

@export var hero_class: String = ""
@export var specialization: String = ""
@export var active_skills: Array[String] = []
@export var passive_skills: Array[String] = []
@export var ultimate_skill: String = ""
@export var companion_slug: String = ""
@export var estimated_power: int = 0
@export var synergies: Array[String] = []
@export var strengths: Array[String] = []
@export var weaknesses: Array[String] = []

func from_dict(d: Dictionary) -> BattleBuild:
	hero_class = str(d.get("hero_class", ""))
	specialization = str(d.get("specialization", ""))
	active_skills = []
	var raw_active: Variant = d.get("active_skills", [])
	if raw_active is Array:
		for item: Variant in raw_active:
			active_skills.append(str(item))
	passive_skills = []
	var raw_passive: Variant = d.get("passive_skills", [])
	if raw_passive is Array:
		for item: Variant in raw_passive:
			passive_skills.append(str(item))
	ultimate_skill = str(d.get("ultimate_skill", ""))
	companion_slug = str(d.get("companion_slug", ""))
	estimated_power = int(d.get("estimated_power", 0))
	synergies = []
	var raw_synergies: Variant = d.get("synergies", [])
	if raw_synergies is Array:
		for item: Variant in raw_synergies:
			synergies.append(str(item))
	strengths = []
	var raw_strengths: Variant = d.get("strengths", [])
	if raw_strengths is Array:
		for item: Variant in raw_strengths:
			strengths.append(str(item))
	weaknesses = []
	var raw_weaknesses: Variant = d.get("weaknesses", [])
	if raw_weaknesses is Array:
		for item: Variant in raw_weaknesses:
			weaknesses.append(str(item))
	return self

func to_dict() -> Dictionary:
	return {
		"hero_class": hero_class,
		"specialization": specialization,
		"active_skills": active_skills,
		"passive_skills": passive_skills,
		"ultimate_skill": ultimate_skill,
		"companion_slug": companion_slug,
		"estimated_power": estimated_power,
		"synergies": synergies,
		"strengths": strengths,
		"weaknesses": weaknesses
	}
