extends GutTest

func test_basic_math() -> void:
	assert_eq(1 + 1, 2)

func test_string() -> void:
	assert_eq("hello".length(), 5)
