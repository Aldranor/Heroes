extends Node

const ONBOARDING_COMPLETE_KEY: String = "onboarding_complete"
const ONBOARDING_STEP_KEY: String = "onboarding_step"
const ONBOARDING_AVATAR_KEY: String = "onboarding_avatar"
const ONBOARDING_COMPANION_KEY: String = "onboarding_companion"

signal onboarding_completed()

func is_onboarded() -> bool:
	var result: Variant = LocalStorage.load_data(ONBOARDING_COMPLETE_KEY, false)
	return bool(result)

func get_saved_step() -> int:
	var result: Variant = LocalStorage.load_data(ONBOARDING_STEP_KEY, 0)
	return int(result)

func save_step(step: int) -> void:
	LocalStorage.save(ONBOARDING_STEP_KEY, step)

func save_avatar_state(data: Dictionary) -> void:
	LocalStorage.save(ONBOARDING_AVATAR_KEY, data)

func get_avatar_state() -> Dictionary:
	var result: Variant = LocalStorage.load_data(ONBOARDING_AVATAR_KEY, {})
	if result is Dictionary:
		return result
	return {}

func save_companion_slug(slug: String) -> void:
	LocalStorage.save(ONBOARDING_COMPANION_KEY, slug)

func get_companion_slug() -> String:
	var result: Variant = LocalStorage.load_data(ONBOARDING_COMPANION_KEY, "")
	return str(result)

func complete_onboarding(data: Dictionary) -> ApiResponse:
	if MockMode.enabled:
		LocalStorage.save(ONBOARDING_COMPLETE_KEY, true)
		LocalStorage.delete(ONBOARDING_STEP_KEY)
		emit_signal("onboarding_completed")
		var resp: ApiResponse = ApiResponse.new(true, 200, {"message": "Onboarding completed (mock)"}, {}, "", false)
		return resp

	var avatar_data: Dictionary = data.get("avatar", {})
	var companion_slug: String = str(data.get("companion_slug", ""))

	var avatar_resp: ApiResponse = await ApiClient.put("/avatar", avatar_data)
	if not avatar_resp.success:
		return avatar_resp

	if companion_slug != "":
		var unlock_resp: ApiResponse = await ApiClient.post(
			"/companions/%s/unlock" % companion_slug,
			{"method": "starter"}
		)
		if not unlock_resp.success:
			return unlock_resp

	var complete_resp: ApiResponse = await ApiClient.post("/onboarding/complete", {})
	if not complete_resp.success:
		return complete_resp

	LocalStorage.save(ONBOARDING_COMPLETE_KEY, true)
	LocalStorage.delete(ONBOARDING_STEP_KEY)
	emit_signal("onboarding_completed")
	return complete_resp

func reset() -> void:
	LocalStorage.delete(ONBOARDING_COMPLETE_KEY)
	LocalStorage.delete(ONBOARDING_STEP_KEY)
	LocalStorage.delete(ONBOARDING_AVATAR_KEY)
	LocalStorage.delete(ONBOARDING_COMPANION_KEY)

func get_state() -> Dictionary:
	return {
		"is_onboarded": is_onboarded(),
		"saved_step": get_saved_step(),
		"avatar_state": get_avatar_state(),
		"companion_slug": get_companion_slug(),
	}
