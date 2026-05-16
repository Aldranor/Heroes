extends "res://addons/gut/test.gd"

func before_each() -> void:
	LocalStorage.clear_all()

func after_each() -> void:
	LocalStorage.clear_all()

func test_save_and_load_plain_value() -> void:
	LocalStorage.save("player_level", 12)
	var level: Variant = LocalStorage.load_data("player_level", 0)
	assert_eq(float(level), 12.0)

func test_save_and_load_sensitive_token() -> void:
	LocalStorage.save("secure_auth_token", "token_abc")
	var token: Variant = LocalStorage.load_data("secure_auth_token", "")
	assert_eq(token, "token_abc")

func test_exists_and_delete() -> void:
	var key: String = "inventory"
	assert_false(LocalStorage.exists(key))
	LocalStorage.save(key, {"gold": 15})
	assert_true(LocalStorage.exists(key))
	LocalStorage.delete(key)
	assert_false(LocalStorage.exists(key))
