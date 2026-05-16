extends Node
class_name SpriteCompositor

static func compose(base_sheet: Texture2D, overlays: Array[Texture2D]) -> ImageTexture:
	if base_sheet == null:
		return null

	var base_img: Image = base_sheet.get_image()
	if base_img == null:
		return null
	base_img.convert(Image.FORMAT_RGBA8)
	var result: Image = base_img.duplicate()

	for overlay_tex: Texture2D in overlays:
		if overlay_tex == null:
			continue
		var overlay_img: Image = overlay_tex.get_image()
		if overlay_img == null:
			continue
		overlay_img.convert(Image.FORMAT_RGBA8)

		var w: int = mini(result.get_width(), overlay_img.get_width())
		var h: int = mini(result.get_height(), overlay_img.get_height())
		result.blend_rect(overlay_img, Rect2i(0, 0, w, h), Vector2i.ZERO)

	return ImageTexture.create_from_image(result)

static func apply_palette_shift(texture: Texture2D, palette: Dictionary) -> ImageTexture:
	if texture == null or palette.is_empty():
		return null

	var img: Image = texture.get_image()
	if img == null:
		return null

	img.convert(Image.FORMAT_RGBA8)
	var data: PackedByteArray = img.get_data()
	var width: int = img.get_width()
	var height: int = img.get_height()
	var palette_map: Dictionary = {}

	for source_hex: Variant in palette:
		var target_hex: Variant = palette[source_hex]
		var src: Color = _parse_color(source_hex)
		var tgt: Color = _parse_color(target_hex)
		var src_rgba: int = _color_to_rgba32(src)
		palette_map[src_rgba] = Color(
			tgt.r8 / 255.0,
			tgt.g8 / 255.0,
			tgt.b8 / 255.0,
			tgt.a8 / 255.0
		)

	for y: int in range(height):
		for x: int in range(width):
			var idx: int = (y * width + x) * 4
			var r: int = data[idx]
			var g: int = data[idx + 1]
			var b: int = data[idx + 2]
			var a: int = data[idx + 3]
			var pixel_rgba: int = (r << 24) | (g << 16) | (b << 8) | a

			if palette_map.has(pixel_rgba):
				var new_color: Color = palette_map[pixel_rgba]
				data[idx] = new_color.r8
				data[idx + 1] = new_color.g8
				data[idx + 2] = new_color.b8
				data[idx + 3] = new_color.a8

	var new_img: Image = Image.create_from_data(width, height, false, Image.FORMAT_RGBA8, data)
	if new_img == null:
		return null
	return ImageTexture.create_from_image(new_img)

static func _parse_color(hex: Variant) -> Color:
	if hex is Color:
		return hex
	if hex is String:
		if hex.begins_with("#"):
			return Color(hex)
		return Color("#" + hex)
	return Color.WHITE

static func _color_to_rgba32(c: Color) -> int:
	return (c.r8 << 24) | (c.g8 << 16) | (c.b8 << 8) | c.a8
