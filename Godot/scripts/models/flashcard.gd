extends Resource
class_name Flashcard

@export var id: int = 0
@export var front_text: String = ""
@export var back_text: String = ""
@export var front_image_url: String = ""
@export var back_image_url: String = ""
@export var ease_factor: float = 2.5
@export var interval_days: int = 1
@export var repetitions: int = 0
@export var next_review_at: String = ""

## Hydrate la resource Flashcard depuis un dictionnaire.
func from_dict(d: Dictionary) -> Flashcard:
	id = int(d.get("id", 0))
	front_text = str(d.get("front_text", ""))
	back_text = str(d.get("back_text", ""))
	front_image_url = str(d.get("front_image_url", ""))
	back_image_url = str(d.get("back_image_url", ""))
	ease_factor = float(d.get("ease_factor", 2.5))
	interval_days = int(d.get("interval_days", 1))
	repetitions = int(d.get("repetitions", 0))
	next_review_at = str(d.get("next_review_at", ""))
	return self

## Convertit la resource Flashcard vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"front_text": front_text,
		"back_text": back_text,
		"front_image_url": front_image_url,
		"back_image_url": back_image_url,
		"ease_factor": ease_factor,
		"interval_days": interval_days,
		"repetitions": repetitions,
		"next_review_at": next_review_at
	}
