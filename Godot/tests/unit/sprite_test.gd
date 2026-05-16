extends "res://addons/gut/test.gd"

var _test_texture: Texture2D = null
var _test_overlay: Texture2D = null

func before_all() -> void:
	_test_texture = _create_test_sheet()
	_test_overlay = _create_test_overlay()

func after_all() -> void:
	_test_texture = null
	_test_overlay = null

func test_sprite_loader_load_local_returns_null_for_missing() -> void:
	var result: Texture2D = SpriteLoader.load_local("res://nonexistent.png")
	assert_null(result)

func test_sprite_loader_load_local_loads_existing_image() -> void:
	var img: Image = Image.create(80, 64, false, Image.FORMAT_RGBA8)
	img.fill(Color.RED)
	var path: String = "user://test_sprite_loader_local.png"
	img.save_png(path)
	var tex: Texture2D = SpriteLoader.load_local(path)
	assert_not_null(tex)
	assert_eq(tex.get_width(), 80)
	assert_eq(tex.get_height(), 64)
	DirAccess.remove_absolute(path)

func test_sprite_loader_configure_animator_sets_frame_dimensions() -> void:
	var animator: SpriteAnimator = SpriteAnimator.new()
	SpriteLoader.configure_animator(animator, _test_texture)

	assert_eq(animator.frame_width, 80)
	assert_eq(animator.frame_height, 64)
	assert_eq(animator.col_count, 10)
	assert_eq(animator.row_count, 7)
	animator.free()

func test_sprite_loader_configure_animator_sets_all_animation_states() -> void:
	var animator: SpriteAnimator = SpriteAnimator.new()
	SpriteLoader.configure_animator(animator, _test_texture)

	var states: Dictionary = animator.animation_states
	assert_true(states.has("idle"), "idle state should exist")
	assert_true(states.has("walk"), "walk state should exist")
	assert_true(states.has("attack"), "attack state should exist")
	assert_true(states.has("hurt"), "hurt state should exist")
	assert_true(states.has("death"), "death state should exist")
	assert_true(states.has("cast"), "cast state should exist")
	assert_true(states.has("special"), "special state should exist")
	animator.free()

func test_sprite_loader_configure_animator_idle_properties() -> void:
	var animator: SpriteAnimator = SpriteAnimator.new()
	SpriteLoader.configure_animator(animator, _test_texture)

	var idle: Dictionary = animator.animation_states["idle"]
	assert_eq(idle["row"], 0, "idle row")
	assert_eq(idle["frames"], 4, "idle frames")
	assert_eq(idle["fps"], 6, "idle fps")
	assert_true(idle["loop"], "idle loop")
	animator.free()

func test_sprite_loader_configure_animator_attack_properties() -> void:
	var animator: SpriteAnimator = SpriteAnimator.new()
	SpriteLoader.configure_animator(animator, _test_texture)

	var attack: Dictionary = animator.animation_states["attack"]
	assert_eq(attack["row"], 2, "attack row")
	assert_eq(attack["frames"], 8, "attack frames")
	assert_eq(attack["fps"], 12, "attack fps")
	assert_false(attack["loop"], "attack loop should be false")
	animator.free()

func test_sprite_loader_configure_animator_starts_playing_idle() -> void:
	var animator: SpriteAnimator = SpriteAnimator.new()
	SpriteLoader.configure_animator(animator, _test_texture)
	assert_eq(animator.current_state, "idle")
	animator.free()

func test_sprite_loader_remote_cache_hit() -> void:
	var fake_url: String = "https://cdn.test/sprite.png"
	var cache_dir: String = "user://sprite_cache"
	if not DirAccess.dir_exists_absolute(cache_dir):
		DirAccess.make_dir_recursive_absolute(cache_dir)

	var cache_key: String = fake_url.md5_text()
	var cache_path: String = cache_dir.path_join("%s.png" % cache_key)

	var img: Image = Image.create(80, 64, false, Image.FORMAT_RGBA8)
	img.fill(Color.BLUE)
	img.save_png(cache_path)

	var tex: Texture2D = await SpriteLoader.load_remote(fake_url)
	assert_not_null(tex)
	assert_eq(tex.get_width(), 80)
	assert_eq(tex.get_height(), 64)
	DirAccess.remove_absolute(cache_path)

func test_sprite_loader_remote_returns_null_for_empty_url() -> void:
	var tex: Texture2D = await SpriteLoader.load_remote("")
	assert_null(tex)

func test_sprite_loader_hash_url() -> void:
	var url: String = "https://example.com/sprite.png"
	var h1: String = SpriteLoader.hash_url(url)
	var h2: String = SpriteLoader.hash_url(url)
	assert_eq(h1, h2)
	assert_true(h1.length() > 0)

func test_sprite_compositor_compose_returns_null_for_null_base() -> void:
	var result: ImageTexture = SpriteCompositor.compose(null, [])
	assert_null(result)

func test_sprite_compositor_compose_returns_texture() -> void:
	var result: ImageTexture = SpriteCompositor.compose(_test_texture, [])
	assert_not_null(result)
	assert_eq(result.get_width(), 80)
	assert_eq(result.get_height(), 64)

func test_sprite_compositor_compose_with_overlay() -> void:
	var result: ImageTexture = SpriteCompositor.compose(_test_texture, [_test_overlay])
	assert_not_null(result)
	assert_eq(result.get_width(), 80)
	assert_eq(result.get_height(), 64)

func test_sprite_compositor_compose_ignores_null_overlays() -> void:
	var result: ImageTexture = SpriteCompositor.compose(_test_texture, [null, _test_overlay, null])
	assert_not_null(result)

func test_sprite_compositor_palette_shift_returns_null_for_null_texture() -> void:
	var result: ImageTexture = SpriteCompositor.apply_palette_shift(null, {"FF0000": "00FF00"})
	assert_null(result)

func test_sprite_compositor_palette_shift_returns_null_for_empty_palette() -> void:
	var result: ImageTexture = SpriteCompositor.apply_palette_shift(_test_texture, {})
	assert_null(result)

func test_sprite_compositor_palette_shift_replaces_color() -> void:
	var img: Image = Image.create(4, 1, false, Image.FORMAT_RGBA8)
	img.set_pixel(0, 0, Color.RED)
	img.set_pixel(1, 0, Color.GREEN)
	img.set_pixel(2, 0, Color.BLUE)
	img.set_pixel(3, 0, Color.WHITE)
	var tex: ImageTexture = ImageTexture.create_from_image(img)

	var result: ImageTexture = SpriteCompositor.apply_palette_shift(tex, {"#ff0000": "#00ff00"})
	assert_not_null(result)

	var result_img: Image = result.get_image()
	var pixel0: Color = result_img.get_pixel(0, 0)
	assert_almost_eq(pixel0.r, 0.0, 0.01, "red channel should be 0")
	assert_almost_eq(pixel0.g, 1.0, 0.01, "green channel should be 255")
	assert_almost_eq(pixel0.b, 0.0, 0.01, "blue channel should be 0")

func test_sprite_compositor_color_parsing() -> void:
	var c: Color = SpriteCompositor._parse_color("#ff8800")
	assert_almost_eq(c.r8, 255, 1)
	assert_almost_eq(c.g8, 136, 1)
	assert_almost_eq(c.b8, 0, 1)

func test_sprite_compositor_color_parsing_without_hash() -> void:
	var c: Color = SpriteCompositor._parse_color("ff8800")
	assert_almost_eq(c.r8, 255, 1)
	assert_almost_eq(c.g8, 136, 1)
	assert_almost_eq(c.b8, 0, 1)

func test_sprite_compositor_color_parsing_returns_color_directly() -> void:
	var c: Color = SpriteCompositor._parse_color(Color(0.5, 0.5, 0.5))
	assert_almost_eq(c.r, 0.5, 0.01)

func _create_test_sheet() -> Texture2D:
	var img: Image = Image.create(80, 64, false, Image.FORMAT_RGBA8)
	img.fill(Color(0.2, 0.2, 0.8, 1.0))
	for x: int in range(0, 80, 8):
		for y: int in range(0, 64, 8):
			if (((x >> 3) + (y >> 3)) % 2) == 0:
				img.set_pixel(x, y, Color.WHITE)
	return ImageTexture.create_from_image(img)

func _create_test_overlay() -> Texture2D:
	var img: Image = Image.create(80, 64, false, Image.FORMAT_RGBA8)
	img.fill(Color(0.0, 0.0, 0.0, 0.0))
	for x: int in range(20, 40):
		for y: int in range(16, 48):
			img.set_pixel(x, y, Color(1.0, 0.0, 0.0, 0.5))
	return ImageTexture.create_from_image(img)
