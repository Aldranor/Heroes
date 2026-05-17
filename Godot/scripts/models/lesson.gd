extends Resource
class_name Lesson

const Flashcard = preload("res://scripts/models/flashcard.gd")
const Question = preload("res://scripts/models/question.gd")

@export var id: int = 0
@export var title: String = ""
@export var description: String = ""
@export var domain: String = ""
@export var category: String = ""
@export var topic: String = ""
@export var difficulty: int = 1
@export var xp_reward: int = 0
@export var sections: Array[Dictionary] = []
@export var flashcards: Array[Flashcard] = []
@export var questions: Array[Question] = []
@export var user_progress: Dictionary = {}

func from_dict(d: Dictionary) -> Lesson:
	id = int(d.get("id", 0))
	title = str(d.get("title", ""))
	description = str(d.get("description", ""))
	domain = str(d.get("domain", ""))
	category = str(d.get("category", ""))
	topic = str(d.get("topic", ""))
	difficulty = int(d.get("difficulty", 1))
	xp_reward = int(d.get("xp_reward", 0))
	sections = []
	var raw_sections: Variant = d.get("sections", [])
	if raw_sections is Array:
		for item: Variant in raw_sections:
			if item is Dictionary:
				sections.append({
					"title": str(item.get("title", "")),
					"content": str(item.get("content", "")),
					"is_collapsible": bool(item.get("is_collapsible", false))
				})
	flashcards = []
	var raw_flashcards: Variant = d.get("flashcards", [])
	if raw_flashcards is Array:
		for item: Variant in raw_flashcards:
			if item is Flashcard:
				flashcards.append(item)
			elif item is Dictionary:
				flashcards.append(Flashcard.new().from_dict(item))
	questions = []
	var raw_questions: Variant = d.get("questions", [])
	if raw_questions is Array:
		for item: Variant in raw_questions:
			if item is Question:
				questions.append(item)
			elif item is Dictionary:
				questions.append(Question.new().from_dict(item))
	var raw_progress: Variant = d.get("user_progress", {})
	if raw_progress is Dictionary:
		user_progress = raw_progress
	return self

func to_dict() -> Dictionary:
	var section_data: Array[Dictionary] = []
	for s: Dictionary in sections:
		section_data.append({
			"title": s.get("title", ""),
			"content": s.get("content", ""),
			"is_collapsible": s.get("is_collapsible", false)
		})
	var flashcard_data: Array[Dictionary] = []
	for f: Flashcard in flashcards:
		flashcard_data.append(f.to_dict())
	var question_data: Array[Dictionary] = []
	for q: Question in questions:
		question_data.append(q.to_dict())
	return {
		"id": id,
		"title": title,
		"description": description,
		"domain": domain,
		"category": category,
		"topic": topic,
		"difficulty": difficulty,
		"xp_reward": xp_reward,
		"sections": section_data,
		"flashcards": flashcard_data,
		"questions": question_data,
		"user_progress": user_progress
	}
