extends Control

signal data_changed(data: Dictionary)

const MOCK = preload("res://mock/avatar_options.gd")

var selected_key: String = ""
var selected_color: Color = Color.WHITE

@onready var _preview_rect: TextureRect = %PreviewRect
@onready var _preview_container: Control = %PreviewContainer
@onready var _body_grid: GridContainer = %BodyGrid
@onready var _color_grid: GridContainer = %ColorGrid
@onready var _title_label: Label = %TitleLabel

func _ready() -> void:
	_build_body_options()
	_build_color_picker()

func set_data(data: Dictionary) -> void:
	if data.has("selected_body_key") and str(data["selected_body_key"]) != "":
		selected_key = str(data["selected_body_key"])
	if data.has("skin_color") and data["skin_color"] is Color:
		selected_color = data["skin_color"]
	_refresh_selection()
	_update_preview()

func get_data() -> Dictionary:
	return {
		"selected_body_key": selected_key,
		"skin_color": selected_color,
	}

func _build_body_options() -> void:
	var options: Array[Dictionary] = MOCK.get_body_options()
	for i: int in range(options.size()):
		var opt: Dictionary = options[i]
		var btn: Button = Button.new()
		btn.custom_minimum_size = Vector2(72, 72)
		btn.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		btn.toggle_mode = true
		btn.button_group = ButtonGroup.new()
		btn.mouse_filter = Control.MOUSE_FILTER_PASS

		var placeholder: ColorRect = ColorRect.new()
		placeholder.custom_minimum_size = Vector2(48, 48)
		placeholder.color = _get_body_color(opt.get("category", "neutre"))
		placeholder.mouse_filter = Control.MOUSE_FILTER_IGNORE
		btn.add_child(placeholder)

		var label: Label = Label.new()
		label.text = str(opt.get("label", ""))
		label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
		label.vertical_alignment = VERTICAL_ALIGNMENT_BOTTOM
		label.size_flags_vertical = Control.SIZE_SHRINK_END
		label.mouse_filter = Control.MOUSE_FILTER_IGNORE
		btn.add_child(label)

		var key: String = str(opt.get("key", ""))
		btn.pressed.connect(_on_body_selected.bind(key, btn))
		_body_grid.add_child(btn)

		if key == selected_key:
			btn.button_pressed = true
			_refresh_selection()

func _build_color_picker() -> void:
	var colors: Array[Color] = MOCK.get_skin_colors()
	for c: Color in colors:
		var btn: Button = Button.new()
		btn.custom_minimum_size = Vector2(28, 28)
		btn.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		btn.toggle_mode = true
		btn.button_group = ButtonGroup.new()

		var rect: ColorRect = ColorRect.new()
		rect.custom_minimum_size = Vector2(20, 20)
		rect.color = c
		rect.mouse_filter = Control.MOUSE_FILTER_IGNORE
		btn.add_child(rect)

		btn.pressed.connect(_on_color_selected.bind(c, btn))
		_color_grid.add_child(btn)

		if c.is_equal_approx(selected_color):
			btn.button_pressed = true

func _on_body_selected(key: String, btn: Button) -> void:
	if not btn.button_pressed:
		return
	selected_key = key
	AudioManager.play_sfx("click", -5.0)
	_refresh_selection()
	_update_preview()
	_animate_option(btn)
	emit_changed()

func _on_color_selected(color: Color, btn: Button) -> void:
	if not btn.button_pressed:
		return
	selected_color = color
	AudioManager.play_sfx("click", -5.0)
	_update_preview()
	emit_changed()

func _refresh_selection() -> void:
	var idx: int = 0
	for child: Node in _body_grid.get_children():
		if child is Button:
			var key: String = ""
			var btn: Button = child
			for grandchild: Node in btn.get_children():
				if grandchild is Label:
					key = _find_key_by_label(grandchild.text)
					break
			if key == selected_key:
				_apply_selected_style(btn, true)
			else:
				_apply_selected_style(btn, false)
		idx += 1

func _find_key_by_label(label_text: String) -> String:
	for opt: Dictionary in MOCK.get_body_options():
		if str(opt.get("label", "")) == label_text:
			return str(opt.get("key", ""))
	return ""

func _apply_selected_style(btn: Button, selected: bool) -> void:
	var normal: StyleBoxFlat = StyleBoxFlat.new()
	if selected:
		normal.bg_color = Color("d4af37")
		normal.border_color = Color("b8960c")
	else:
		normal.bg_color = Color("2d2d2d")
		normal.border_color = Color("4a4540")
	normal.border_width_left = 2
	normal.border_width_top = 2
	normal.border_width_right = 2
	normal.border_width_bottom = 2
	btn.add_theme_stylebox_override("normal", normal)
	btn.add_theme_stylebox_override("pressed", normal)
	btn.add_theme_stylebox_override("hover", normal)

func _update_preview() -> void:
	_preview_container.modulate = selected_color
	if selected_key != "":
		var label: String = selected_key
		_preview_rect.tooltip_text = label

func _animate_option(btn: Button) -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BACK)
	btn.scale = Vector2(1.1, 1.1)
	tween.tween_property(btn, "scale", Vector2(1, 1), 0.2)

func emit_changed() -> void:
	data_changed.emit(get_data())

func _get_body_color(category: String) -> Color:
	match category.to_lower():
		"feminin":
			return Color("e8b88a")
		"masculin":
			return Color("b8845c")
		"neutre":
			return Color("d4a574")
		_:
			return Color("c0c0c0")
