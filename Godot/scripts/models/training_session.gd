extends Resource
class_name TrainingSession

const Question = preload("res://scripts/models/question.gd")

@export var id: int = 0
@export var domain: String = ""
@export var category: String = ""
@export var companion_slug: String = ""
@export var mode: String = ""
@export var total_questions: int = 0
@export var current_index: int = 0
@export var current_question: Question = null
@export var power_ups: Dictionary = {}
@export var score: int = 0
@export var xp_earned: int = 0
@export var completed: bool = false

func from_dict(d: Dictionary) -> TrainingSession:
	id = int(d.get("id", 0))
	domain = str(d.get("domain", ""))
	category = str(d.get("category", ""))
	companion_slug = str(d.get("companion_slug", ""))
	mode = str(d.get("mode", ""))
	total_questions = int(d.get("total_questions", 0))
	current_index = int(d.get("current_index", 0))
	var raw_question: Variant = d.get("current_question", {})
	if raw_question is Question:
		current_question = raw_question
	elif raw_question is Dictionary and not raw_question.is_empty():
		current_question = Question.new().from_dict(raw_question)
	else:
		current_question = null
	var raw_power_ups: Variant = d.get("power_ups", {})
	if raw_power_ups is Dictionary:
		power_ups = raw_power_ups
	score = int(d.get("score", 0))
	xp_earned = int(d.get("xp_earned", 0))
	completed = bool(d.get("completed", false))
	return self

func to_dict() -> Dictionary:
	return {
		"id": id,
		"domain": domain,
		"category": category,
		"companion_slug": companion_slug,
		"mode": mode,
		"total_questions": total_questions,
		"current_index": current_index,
		"current_question": current_question.to_dict() if current_question != null else {},
		"power_ups": power_ups,
		"score": score,
		"xp_earned": xp_earned,
		"completed": completed
	}
