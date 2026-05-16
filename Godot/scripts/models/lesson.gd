extends Resource
class_name Lesson

@export var id: int = 0
@export var slug: String = ""
@export var title: String = ""
@export var body_markdown: String = ""
@export var category_id: int = 0
@export var sort_order: int = 0
@export var cover_image_url: String = ""

## Hydrate la resource Lesson depuis un dictionnaire.
func from_dict(d: Dictionary) -> Lesson:
	id = int(d.get("id", 0))
	slug = str(d.get("slug", ""))
	title = str(d.get("title", ""))
	body_markdown = str(d.get("body_markdown", ""))
	category_id = int(d.get("category_id", 0))
	sort_order = int(d.get("sort_order", 0))
	cover_image_url = str(d.get("cover_image_url", ""))
	return self

## Convertit la resource Lesson vers un dictionnaire JSON.
func to_dict() -> Dictionary:
	return {
		"id": id,
		"slug": slug,
		"title": title,
		"body_markdown": body_markdown,
		"category_id": category_id,
		"sort_order": sort_order,
		"cover_image_url": cover_image_url
	}
