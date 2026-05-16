extends Node

const API_RESPONSE_MODEL = preload("res://scripts/models/api_response.gd")

const ENV_PATH: String = "res://config/env.cfg"
const TOKEN_STORAGE_KEY: String = "secure_auth_token"
const DEFAULT_BASE_URL: String = "https://api.codex.example/api/v1"
const DEFAULT_TIMEOUT_SECONDS: float = 10.0
const DEFAULT_RETRY_ATTEMPTS: int = 3
const DEFAULT_RETRY_BASE_DELAY_SECONDS: float = 0.5
const DEFAULT_PING_INTERVAL_SECONDS: float = 15.0
const DEFAULT_HEALTHCHECK_PATH: String = "/health"

signal request_started(method, path)
signal request_completed(method, path, response)
signal request_failed(method, path, error_code, error_message)
signal unauthorized()

var base_url: String = DEFAULT_BASE_URL
var auth_token: String = ""
var is_online: bool = true

var timeout_seconds: float = DEFAULT_TIMEOUT_SECONDS
var retry_attempts: int = DEFAULT_RETRY_ATTEMPTS
var retry_base_delay_seconds: float = DEFAULT_RETRY_BASE_DELAY_SECONDS
var ping_interval_seconds: float = DEFAULT_PING_INTERVAL_SECONDS
var healthcheck_path: String = DEFAULT_HEALTHCHECK_PATH
var log_requests: bool = true
var mock_offline: bool = false

var _ping_timer: Timer = Timer.new()

func _ready() -> void:
	_load_env_config()
	call_deferred("_initialize_after_autoloads")

func _initialize_after_autoloads() -> void:
	auth_token = str(LocalStorage.load_data(TOKEN_STORAGE_KEY, ""))
	_setup_ping_timer()
	_start_initial_ping()

## Définit le token d'authentification (persisté localement).
func set_auth_token(token: String) -> void:
	auth_token = token.strip_edges()
	if auth_token == "":
		LocalStorage.delete(TOKEN_STORAGE_KEY)
	else:
		LocalStorage.save(TOKEN_STORAGE_KEY, auth_token)

## Supprime le token d'authentification actif.
func clear_auth_token() -> void:
	auth_token = ""
	LocalStorage.delete(TOKEN_STORAGE_KEY)

## GET JSON.
func request_get(path: String, params: Dictionary = {}) -> ApiResponse:
	var url: String = _build_url(path, params)
	return await _request_with_retry(HTTPClient.METHOD_GET, "GET", path, url, "", _build_headers(true), false)

## POST JSON.
func post(path: String, body: Dictionary = {}) -> ApiResponse:
	var url: String = _build_url(path)
	var payload: String = JSON.stringify(body)
	return await _request_with_retry(HTTPClient.METHOD_POST, "POST", path, url, payload, _build_headers(true), false)

## PUT JSON.
func put(path: String, body: Dictionary = {}) -> ApiResponse:
	var url: String = _build_url(path)
	var payload: String = JSON.stringify(body)
	return await _request_with_retry(HTTPClient.METHOD_PUT, "PUT", path, url, payload, _build_headers(true), false)

## DELETE JSON.
func delete(path: String) -> ApiResponse:
	var url: String = _build_url(path)
	return await _request_with_retry(HTTPClient.METHOD_DELETE, "DELETE", path, url, "", _build_headers(true), false)

## Upload un fichier brut vers l'API.
func upload_file(path: String, file_path: String) -> ApiResponse:
	if not FileAccess.file_exists(file_path):
		var missing_response: ApiResponse = API_RESPONSE_MODEL.new(false, 0, null, {}, "File not found: %s" % file_path, false, PackedByteArray())
		emit_signal("request_failed", "UPLOAD", path, 0, missing_response.message)
		return missing_response
	var file: FileAccess = FileAccess.open(file_path, FileAccess.READ)
	if file == null:
		var open_error_response: ApiResponse = API_RESPONSE_MODEL.new(false, 0, null, {}, "Cannot open file: %s" % file_path, false, PackedByteArray())
		emit_signal("request_failed", "UPLOAD", path, 0, open_error_response.message)
		return open_error_response
	var bytes: PackedByteArray = file.get_buffer(file.get_length())
	var url: String = _build_url(path)
	var headers: PackedStringArray = _build_headers(false)
	headers.append("Content-Type: application/octet-stream")
	headers.append("X-File-Name: %s" % file_path.get_file())
	return await _request_with_retry(HTTPClient.METHOD_POST, "UPLOAD", path, url, bytes, headers, true)

func _setup_ping_timer() -> void:
	_ping_timer.wait_time = ping_interval_seconds
	_ping_timer.one_shot = false
	_ping_timer.autostart = true
	_ping_timer.timeout.connect(_on_ping_timer_timeout)
	add_child(_ping_timer)

func _start_initial_ping() -> void:
	_on_ping_timer_timeout()

func _on_ping_timer_timeout() -> void:
	if mock_offline:
		is_online = false
		return
	var requester: HTTPRequest = HTTPRequest.new()
	requester.timeout = min(3.0, timeout_seconds)
	add_child(requester)
	var ping_headers: PackedStringArray = PackedStringArray(["Accept: application/json"])
	requester.request_completed.connect(_on_ping_request_completed.bind(requester), CONNECT_ONE_SHOT)
	var error: int = requester.request(_build_url(healthcheck_path), ping_headers, HTTPClient.METHOD_GET)
	if error != OK:
		is_online = false
		requester.queue_free()
		return

func _on_ping_request_completed(result: int, response_code: int, _headers: PackedStringArray, _body: PackedByteArray, requester: HTTPRequest) -> void:
	is_online = (result == HTTPRequest.RESULT_SUCCESS) and (response_code > 0)
	requester.queue_free()

func _load_env_config() -> void:
	var config: ConfigFile = ConfigFile.new()
	var load_error: int = config.load(ENV_PATH)
	if load_error != OK:
		return
	base_url = str(config.get_value("api", "base_url", DEFAULT_BASE_URL))
	log_requests = bool(config.get_value("debug", "log_requests", true))
	mock_offline = bool(config.get_value("debug", "mock_offline", false))
	timeout_seconds = float(config.get_value("api", "timeout_seconds", DEFAULT_TIMEOUT_SECONDS))

func _build_url(path: String, params: Dictionary = {}) -> String:
	var normalized_path: String = path.strip_edges()
	if not normalized_path.begins_with("/"):
		normalized_path = "/%s" % normalized_path
	var url: String = "%s%s" % [base_url.rstrip("/"), normalized_path]
	if params.is_empty():
		return url
	var query_parts: PackedStringArray = PackedStringArray()
	for key: Variant in params.keys():
		var query_key: String = String(str(key)).uri_encode()
		var query_value: String = String(str(params[key])).uri_encode()
		query_parts.append("%s=%s" % [query_key, query_value])
	return "%s?%s" % [url, "&".join(query_parts)]

func _build_headers(with_content_type: bool) -> PackedStringArray:
	var headers: PackedStringArray = PackedStringArray()
	headers.append("Accept: application/json")
	if with_content_type:
		headers.append("Content-Type: application/json")
	if auth_token != "":
		headers.append("Authorization: Bearer %s" % auth_token)
	return headers

func _request_with_retry(
	method: int,
	method_label: String,
	path: String,
	url: String,
	payload: Variant,
	headers: PackedStringArray,
	use_raw_request: bool
) -> ApiResponse:
	emit_signal("request_started", method_label, path)
	if log_requests:
		print("[ApiClient] %s %s" % [method_label, url])

	if mock_offline:
		var mock_offline_response: ApiResponse = API_RESPONSE_MODEL.new(false, 0, null, {}, "Offline mode enabled (mock_offline=true)", true, PackedByteArray())
		emit_signal("request_failed", method_label, path, 0, mock_offline_response.message)
		return mock_offline_response
	if not is_online:
		var offline_response: ApiResponse = API_RESPONSE_MODEL.new(false, 0, null, {}, "No internet connection", true, PackedByteArray())
		emit_signal("request_failed", method_label, path, 0, offline_response.message)
		emit_signal("request_completed", method_label, path, offline_response)
		return offline_response

	var last_response: ApiResponse = API_RESPONSE_MODEL.new(false, 0, null, {}, "Unknown error", false, PackedByteArray())
	for attempt: int in range(retry_attempts):
		var attempt_response: ApiResponse = await _perform_request(method, url, payload, headers, use_raw_request)
		if attempt_response.success:
			is_online = true
			emit_signal("request_completed", method_label, path, attempt_response)
			return attempt_response

		var is_network_error: bool = attempt_response.is_offline or attempt_response.status_code == 0
		if not is_network_error:
			last_response = attempt_response
			break
		last_response = attempt_response
		if attempt < retry_attempts - 1:
			var delay: float = retry_base_delay_seconds * pow(2.0, attempt)
			await get_tree().create_timer(delay).timeout

	is_online = not last_response.is_offline
	emit_signal("request_failed", method_label, path, last_response.status_code, last_response.message)
	emit_signal("request_completed", method_label, path, last_response)
	return last_response

func _perform_request(
	method: int,
	url: String,
	payload: Variant,
	headers: PackedStringArray,
	use_raw_request: bool
) -> ApiResponse:
	var requester: HTTPRequest = HTTPRequest.new()
	requester.timeout = timeout_seconds
	add_child(requester)

	var request_error: int = OK
	if use_raw_request:
		request_error = requester.request_raw(url, headers, method, payload)
	else:
		request_error = requester.request(url, headers, method, String(payload))

	if request_error != OK:
		requester.queue_free()
		return API_RESPONSE_MODEL.new(false, 0, null, {}, "Network request failed to start", true, PackedByteArray())

	var result_data: Array = await requester.request_completed
	requester.queue_free()

	var result: int = int(result_data[0])
	var status_code: int = int(result_data[1])
	var response_body: PackedByteArray = result_data[3]

	if result != HTTPRequest.RESULT_SUCCESS:
		return API_RESPONSE_MODEL.new(false, status_code, null, {}, "Network error (%d)" % result, true, response_body)

	var parsed_data: Variant = _parse_json_or_text(response_body)
	var response_message: String = _extract_message(parsed_data, status_code)
	var response_errors: Dictionary = {}
	if status_code == 422 and typeof(parsed_data) == TYPE_DICTIONARY:
		response_errors = parsed_data.get("errors", {})
	var success: bool = status_code >= 200 and status_code < 300

	var response: ApiResponse = API_RESPONSE_MODEL.new(success, status_code, parsed_data, response_errors, response_message, false, response_body)
	if status_code == 401:
		clear_auth_token()
		emit_signal("unauthorized")
		response.success = false
		response.message = "Unauthorized"
	return response

func _parse_json_or_text(body: PackedByteArray) -> Variant:
	if body.is_empty():
		return {}
	var text: String = body.get_string_from_utf8()
	if text.strip_edges() == "":
		return {}
	var parser: JSON = JSON.new()
	var parse_error: int = parser.parse(text)
	if parse_error == OK:
		return parser.data
	return text

func _extract_message(data: Variant, status_code: int) -> String:
	if typeof(data) == TYPE_DICTIONARY:
		var dict_data: Dictionary = data
		if dict_data.has("message"):
			return str(dict_data["message"])
		if dict_data.has("error"):
			return str(dict_data["error"])
	if status_code >= 200 and status_code < 300:
		return "OK"
	return "Request failed"
