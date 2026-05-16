extends Resource
class_name Question

const Answer = preload("res://scripts/models/answer.gd")

@export var id: int = 0
@export var prompt: String = ""
@export var image_url: String = ""
@export var difficulty: int = 1
@export var explanation: String = ""
@export var answers: Array[Answer] = []

## Hydrate la resource Question depuis un dictionnaire.
func from_dict(d: Dictionary) -> Question:
	id = int(d.get("id", 0))
	prompt = str(d.get("prompt", ""))
	image_url = str(d.get("image_url", ""))
	difficulty = int(d.get("difficulty", 1))
	explanation = str(d.get("explanation", ""))

	answers = []
	var raw_answers: Variant = d.get("answers", [])
	if raw_answers is Array:
		for item: Variant in raw_answers:
			if item is Answer:
				answers.append(item)
			elif item is Dictionary:
				answers.append(Answer.new().from_dict(item))
	return self

## Convertit la resource Question vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	var answer_data: Array[Dictionary] = []
	for answer: Answer in answers:
		answer_data.append(answer.to_dict())
	return {
		"id": id,
		"prompt": prompt,
		"image_url": image_url,
		"difficulty": difficulty,
		"explanation": explanation,
		"answers": answer_data
	}
