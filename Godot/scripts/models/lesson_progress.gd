extends Resource
class_name LessonProgress

@export var lesson_id: int = 0
@export var flashcard_id: int = 0
@export var status: String = "viewed"
@export var quiz_answers: Dictionary = {}
@export var completed_at: String = ""

func from_dict(d: Dictionary) -> LessonProgress:
	lesson_id = int(d.get("lesson_id", 0))
	flashcard_id = int(d.get("flashcard_id", 0))
	status = str(d.get("status", "viewed"))
	var raw_answers: Variant = d.get("quiz_answers", {})
	if raw_answers is Dictionary:
		quiz_answers = raw_answers
	completed_at = str(d.get("completed_at", ""))
	return self

func to_dict() -> Dictionary:
	return {
		"lesson_id": lesson_id,
		"flashcard_id": flashcard_id,
		"status": status,
		"quiz_answers": quiz_answers,
		"completed_at": completed_at
	}
