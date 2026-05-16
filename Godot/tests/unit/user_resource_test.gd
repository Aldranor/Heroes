extends "res://addons/gut/test.gd"

const TEST_USER_PATH: String = "user://test_user_state.tres"

func after_each() -> void:
	if FileAccess.file_exists(TEST_USER_PATH):
		DirAccess.remove_absolute(TEST_USER_PATH)

func test_user_resource_can_be_saved_and_loaded() -> void:
	var user: User = User.new().from_dict({
		"id": 42,
		"email": "stephanie@example.com",
		"name": "Stephanie",
		"coins": 120,
		"avatar": {
			"body_key": "body_default",
			"hair_key": "hair_short"
		}
	})

	var save_error: int = ResourceSaver.save(user, TEST_USER_PATH)
	assert_eq(save_error, OK)

	var loaded: Resource = ResourceLoader.load(TEST_USER_PATH)
	assert_true(loaded is User)
	var loaded_user: User = loaded as User
	assert_eq(loaded_user.id, 42)
	assert_eq(loaded_user.email, "stephanie@example.com")
	assert_eq(loaded_user.avatar.body_key, "body_default")
