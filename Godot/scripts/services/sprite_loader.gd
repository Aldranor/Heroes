extends Node
class_name SpriteLoader

const LAYOUT_PATH: String = "res://config/sprite_layout.cfg"
const CACHE_DIR: String = "user://sprite_cache"

static func load_local(path: String) -> Texture2D:
	if path.begins_with("user://"):
		if not FileAccess.file_exists(path):
			return null
		var image: Image = Image.new()
		var load_error: int = image.load(path)
		if load_error != OK:
			return null
		return ImageTexture.create_from_image(image)

	if not ResourceLoader.exists(path):
		return null

	var texture: Texture2D = ResourceLoader.load(path) as Texture2D
	return texture

static func load_remote(url: String) -> Texture2D:
	if url.is_empty():
		return null

	var cache_key: String = url.md5_text()
	var cache_path: String = CACHE_DIR.path_join("%s.png" % cache_key)

	if FileAccess.file_exists(cache_path):
		var img: Image = Image.new()
		var err: int = img.load(cache_path)
		if err == OK:
			return ImageTexture.create_from_image(img)

	var tex: Texture2D = await _download_texture(url)
	if tex != null:
		_save_to_cache(tex, cache_path)
	return tex

static func configure_animator(animator: SpriteAnimator, texture: Texture2D) -> void:
	var layout: Dictionary = _load_layout()

	animator.sheet_texture = texture
	if layout.has("frame"):
		var frame: Dictionary = layout["frame"]
		animator.frame_width = int(frame.get("width", 80))
		animator.frame_height = int(frame.get("height", 64))
		animator.col_count = int(frame.get("cols", 10))
		animator.row_count = int(frame.get("rows", 7))

	var states: Dictionary = {}
	if layout.has("animations"):
		for key: String in layout["animations"]:
			var raw: Variant = layout["animations"][key]
			if raw is Dictionary:
				var anim: Dictionary = raw
				states[key] = {
					"row": int(anim.get("row", 0)),
					"frames": int(anim.get("frames", 1)),
					"fps": int(anim.get("fps", 8)),
					"loop": bool(anim.get("loop", true))
				}
	if not states.is_empty():
		animator.animation_states = states

	animator.current_state = "idle"
	animator.play("idle")

static func hash_url(url: String) -> String:
	return url.md5_text()

static func _load_layout() -> Dictionary:
	var cfg: ConfigFile = ConfigFile.new()
	var err: int = cfg.load(LAYOUT_PATH)
	if err != OK:
		push_error("SpriteLoader: cannot load layout config: %s" % LAYOUT_PATH)
		return {}

	var result: Dictionary = {}
	for section: String in cfg.get_sections():
		var section_data: Dictionary = {}
		for key: String in cfg.get_section_keys(section):
			var raw: Variant = cfg.get_value(section, key)
			section_data[key] = _try_parse_value(raw)
		result[section] = section_data
	return result

static func _try_parse_value(raw: Variant) -> Variant:
	if raw is String and raw.begins_with("{"):
		var result: Variant = JSON.parse_string(String(raw))
		if result != null:
			return result
	return raw

static func _download_texture(url: String) -> Texture2D:
	var temp_parent: Node = Node.new()
	var http: HTTPRequest = HTTPRequest.new()

	var tree: SceneTree = Engine.get_main_loop()
	tree.root.add_child(temp_parent)
	temp_parent.add_child(http)

	var err: int = http.request(url)
	if err != OK:
		push_error("SpriteLoader: HTTP request failed: %d" % err)
		temp_parent.queue_free()
		return null

	var response: Array = await http.request_completed
	temp_parent.queue_free()

	if response.size() < 4:
		return null

	var result: int = response[0]
	if result != HTTPRequest.RESULT_SUCCESS:
		push_error("SpriteLoader: download failed for %s (code: %d)" % [url, result])
		return null

	var response_code: int = response[1]
	if response_code != 200:
		push_error("SpriteLoader: HTTP %d for %s" % [response_code, url])
		return null

	var body: PackedByteArray = response[3]
	var img: Image = Image.new()
	var img_err: int = img.load_png_from_buffer(body)
	if img_err != OK:
		img_err = img.load_webp_from_buffer(body)
	if img_err != OK:
		push_error("SpriteLoader: unsupported image format: %s" % url)
		return null

	return ImageTexture.create_from_image(img)

static func _save_to_cache(tex: Texture2D, cache_path: String) -> void:
	var dir_path: String = cache_path.get_base_dir()
	if not DirAccess.dir_exists_absolute(dir_path):
		DirAccess.make_dir_recursive_absolute(dir_path)
	var img: Image = tex.get_image()
	if img != null:
		img.save_png(cache_path)
