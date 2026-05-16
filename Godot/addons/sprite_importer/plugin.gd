@tool
extends EditorPlugin

const SPRITE_DIR: String = "res://assets/sprites/"
const EXPECTED_WIDTH: int = 800
const EXPECTED_HEIGHT: int = 448
const FRAME_HEIGHT: int = 64
const ALLOWED_HEIGHTS: Array[int] = [448, 512, 576, 640, 704, 768, 832, 896]
const PIXEL_PERFECT_IMPORT: Dictionary = {
	"compress/mode": 0,
	"compress/lossy_quality": 0.0,
	"detect_3d": false,
	"mipmaps/generate": false,
	"flags/repeat": false,
	"flags/filter": false
}

var _filesystem: EditorFileSystem = null

func _enter_tree() -> void:
	_filesystem = get_editor_interface().get_resource_filesystem()
	if _filesystem:
		_filesystem.filesystem_changed.connect(_on_filesystem_changed)
	scan_and_validate()

func _exit_tree() -> void:
	if _filesystem and _filesystem.filesystem_changed.is_connected(_on_filesystem_changed):
		_filesystem.filesystem_changed.disconnect(_on_filesystem_changed)

func _on_filesystem_changed() -> void:
	scan_and_validate()

func scan_and_validate() -> void:
	var dir: DirAccess = DirAccess.open(SPRITE_DIR)
	if dir == null:
		return

	dir.list_dir_begin()
	var file_name: String = dir.get_next()
	while file_name != "":
		if not dir.current_is_dir() and file_name.get_extension().to_lower() == "png":
			var full_path: String = SPRITE_DIR.path_join(file_name)
			_validate_sprite(full_path)
		file_name = dir.get_next()
	dir.list_dir_end()

func _validate_sprite(path: String) -> void:
	var img: Image = Image.new()
	var err: int = img.load(path)
	if err != OK:
		push_warning("SpriteImporter: cannot load %s" % path)
		return

	var w: int = img.get_width()
	var h: int = img.get_height()

	if w != EXPECTED_WIDTH:
		push_warning("SpriteImporter: %s — largeur %d px (attendu: %d)" % [path, w, EXPECTED_WIDTH])

	var valid_height: bool = false
	for allowed: int in ALLOWED_HEIGHTS:
		if h == allowed:
			valid_height = true
			break
	if not valid_height:
		push_warning("SpriteImporter: %s — hauteur %d px invalide (attendu: %d, %d, ...)" % [path, h, EXPECTED_HEIGHT, EXPECTED_HEIGHT + FRAME_HEIGHT])

	_apply_pixel_perfect_import(path)

func _apply_pixel_perfect_import(path: String) -> void:
	var import_path: String = path + ".import"
	var cfg: ConfigFile = ConfigFile.new()

	var exists: bool = FileAccess.file_exists(import_path)
	if exists:
		cfg.load(import_path)

	var changed: bool = false
	for key: String in PIXEL_PERFECT_IMPORT:
		var current: Variant = cfg.get_value("params", key, null)
		var target: Variant = PIXEL_PERFECT_IMPORT[key]
		if current == null or current != target:
			cfg.set_value("params", key, target)
			changed = true

	if changed:
		cfg.save(import_path)
		if _filesystem:
			_filesystem.update_file(path)
