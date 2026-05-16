extends Node

const TOKEN_STORAGE_KEY: String = "secure_auth_token"

func login(email: String, password: String) -> ApiResponse:
	var payload: Dictionary = {
		"email": email,
		"password": password
	}
	var response: ApiResponse = await ApiClient.post("/auth/login", payload)
	if response.success:
		var token: String = _extract_token(response.data)
		if token != "":
			ApiClient.set_auth_token(token)
	return response

func register(email: String, password: String, display_name: String) -> ApiResponse:
	var payload: Dictionary = {
		"email": email,
		"password": password,
		"name": display_name
	}
	return await ApiClient.post("/auth/register", payload)

func logout() -> ApiResponse:
	var response: ApiResponse = await ApiClient.post("/auth/logout", {})
	ApiClient.clear_auth_token()
	return response

func refresh_user() -> ApiResponse:
	var response: ApiResponse = await ApiClient.request_get("/me")
	if response.success and typeof(response.data) == TYPE_DICTIONARY:
		var response_data: Dictionary = response.data
		var payload: Dictionary = response_data
		if response_data.has("data") and typeof(response_data["data"]) == TYPE_DICTIONARY:
			payload = response_data["data"]
		if GameState.has_method("set_current_user_from_dict"):
			GameState.set_current_user_from_dict(payload)
	return response

func is_authenticated() -> bool:
	if ApiClient.auth_token != "":
		return true
	var stored_token: String = str(LocalStorage.load_data(TOKEN_STORAGE_KEY, ""))
	return stored_token != ""

func _extract_token(data: Variant) -> String:
	if typeof(data) != TYPE_DICTIONARY:
		return ""
	var dict_data: Dictionary = data
	if dict_data.has("token"):
		return str(dict_data["token"])
	if dict_data.has("access_token"):
		return str(dict_data["access_token"])
	if dict_data.has("data") and typeof(dict_data["data"]) == TYPE_DICTIONARY:
		var nested: Dictionary = dict_data["data"]
		if nested.has("token"):
			return str(nested["token"])
		if nested.has("access_token"):
			return str(nested["access_token"])
	return ""
