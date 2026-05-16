extends Node

const STORAGE_DIR: String = "user://storage"
const SENSITIVE_PREFIX: String = "secure_"
const ENCRYPTION_SEED: String = "codex_seed_v1"

## Sauvegarde une valeur locale en JSON.
func save(key: String, value: Variant) -> void:
	_ensure_storage_dir()
	var payload: Dictionary = {
		"is_sensitive": _is_sensitive_key(key),
		"value": value
	}
	if payload["is_sensitive"]:
		payload["value"] = _xor_encrypt(String(value))
	var file: FileAccess = FileAccess.open(_key_to_path(key), FileAccess.WRITE)
	if file == null:
		push_error("LocalStorage.save: impossible d'ouvrir le fichier pour %s" % key)
		return
	file.store_string(JSON.stringify(payload))

## Charge une valeur locale ou retourne default si absente/invalide.
func load_data(key: String, default: Variant = null) -> Variant:
	var path: String = _key_to_path(key)
	if not FileAccess.file_exists(path):
		return default
	var file: FileAccess = FileAccess.open(path, FileAccess.READ)
	if file == null:
		return default
	var raw_text: String = file.get_as_text()
	var json: JSON = JSON.new()
	var parse_error: int = json.parse(raw_text)
	if parse_error != OK:
		return default
	var payload: Variant = json.data
	if typeof(payload) != TYPE_DICTIONARY:
		return default
	var data: Dictionary = payload
	var value: Variant = data.get("value", default)
	var is_sensitive: bool = bool(data.get("is_sensitive", false))
	if is_sensitive and value != null:
		return _xor_decrypt(String(value))
	return value

## Supprime une clé locale.
func delete(key: String) -> void:
	var path: String = _key_to_path(key)
	if FileAccess.file_exists(path):
		DirAccess.remove_absolute(path)

## Retourne true si la clé existe.
func exists(key: String) -> bool:
	return FileAccess.file_exists(_key_to_path(key))

## Efface tout le stockage local applicatif.
func clear_all() -> void:
	if not DirAccess.dir_exists_absolute(STORAGE_DIR):
		return
	var dir: DirAccess = DirAccess.open(STORAGE_DIR)
	if dir == null:
		return
	dir.list_dir_begin()
	var file_name: String = dir.get_next()
	while file_name != "":
		if not dir.current_is_dir():
			var absolute_path: String = STORAGE_DIR.path_join(file_name)
			DirAccess.remove_absolute(absolute_path)
		file_name = dir.get_next()
	dir.list_dir_end()

func _ensure_storage_dir() -> void:
	if not DirAccess.dir_exists_absolute(STORAGE_DIR):
		DirAccess.make_dir_recursive_absolute(STORAGE_DIR)

func _key_to_path(key: String) -> String:
	var safe_key: String = key.strip_edges().replace("/", "_")
	return STORAGE_DIR.path_join("%s.json" % safe_key)

func _is_sensitive_key(key: String) -> bool:
	return key.begins_with(SENSITIVE_PREFIX) or key.to_lower().contains("token")

func _xor_encrypt(value: String) -> String:
	var source: PackedByteArray = value.to_utf8_buffer()
	var key_bytes: PackedByteArray = ENCRYPTION_SEED.to_utf8_buffer()
	var output: PackedByteArray = PackedByteArray()
	output.resize(source.size())
	if key_bytes.is_empty():
		return value
	for i: int in range(source.size()):
		output[i] = source[i] ^ key_bytes[i % key_bytes.size()]
	return Marshalls.raw_to_base64(output)

func _xor_decrypt(value: String) -> String:
	var encrypted: PackedByteArray = Marshalls.base64_to_raw(value)
	var key_bytes: PackedByteArray = ENCRYPTION_SEED.to_utf8_buffer()
	if key_bytes.is_empty():
		return value
	var output: PackedByteArray = PackedByteArray()
	output.resize(encrypted.size())
	for i: int in range(encrypted.size()):
		output[i] = encrypted[i] ^ key_bytes[i % key_bytes.size()]
	return output.get_string_from_utf8()
