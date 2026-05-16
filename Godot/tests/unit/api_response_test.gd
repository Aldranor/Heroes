extends "res://addons/gut/test.gd"

func test_to_dict_and_from_dict_roundtrip() -> void:
	var raw: PackedByteArray = PackedByteArray([1, 2, 3, 4])
	var source: ApiResponse = ApiResponse.new(
		true,
		200,
		{"player": "codex"},
		{"field": ["required"]},
		"ok",
		false,
		raw
	)
	var as_dict: Dictionary = source.to_dict()
	var restored: ApiResponse = ApiResponse.from_dict(as_dict)

	assert_true(restored.success)
	assert_eq(restored.status_code, 200)
	assert_eq(restored.data["player"], "codex")
	assert_eq(restored.errors["field"][0], "required")
	assert_eq(restored.message, "ok")
	assert_false(restored.is_offline)
	assert_eq(restored.raw_response, raw)
