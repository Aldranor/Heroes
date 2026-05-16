extends Resource
class_name Answer

@export var id: int = 0
@export var text: String = ""
@export var is_correct: bool = false
@export var explanation: String = ""

## Hydrate la resource Answer depuis un dictionnaire.
func from_dict(d: Dictionary) -> Answer:
	id = int(d.get("id", 0))
	text = str(d.get("text", ""))
	is_correct = bool(d.get("is_correct", false))
	explanation = str(d.get("explanation", ""))
	return self

## Convertit la resource Answer vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"text": text,
		"is_correct": is_correct,
		"explanation": explanation
	}
