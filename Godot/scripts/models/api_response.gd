extends RefCounted
class_name ApiResponse

var success: bool = false
var status_code: int = 0
var data: Variant = null
var errors: Dictionary = {}
var message: String = ""
var is_offline: bool = false
var raw_response: PackedByteArray = PackedByteArray()

func _init(
	p_success: bool = false,
	p_status_code: int = 0,
	p_data: Variant = null,
	p_errors: Dictionary = {},
	p_message: String = "",
	p_is_offline: bool = false,
	p_raw_response: PackedByteArray = PackedByteArray()
) -> void:
	success = p_success
	status_code = p_status_code
	data = p_data
	errors = p_errors.duplicate(true)
	message = p_message
	is_offline = p_is_offline
	raw_response = p_raw_response

## Convertit la réponse en dictionnaire sérialisable JSON.
func to_dict() -> Dictionary:
	return {
		"success": success,
		"status_code": status_code,
		"data": data,
		"errors": errors,
		"message": message,
		"is_offline": is_offline,
		"raw_response_base64": Marshalls.raw_to_base64(raw_response)
	}

## Crée un ApiResponse depuis un dictionnaire sérialisé.
static func from_dict(payload: Dictionary) -> ApiResponse:
	var response: ApiResponse = ApiResponse.new()
	response.success = bool(payload.get("success", false))
	response.status_code = int(payload.get("status_code", 0))
	response.data = payload.get("data", null)
	response.errors = payload.get("errors", {})
	response.message = str(payload.get("message", ""))
	response.is_offline = bool(payload.get("is_offline", false))
	var raw_base64: String = str(payload.get("raw_response_base64", ""))
	if raw_base64 != "":
		response.raw_response = Marshalls.base64_to_raw(raw_base64)
	return response
