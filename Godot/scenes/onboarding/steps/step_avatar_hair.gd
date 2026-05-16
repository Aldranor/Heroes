extends Control

signal data_changed(data: Dictionary)

const MOCK = preload("res://mock/avatar_options.gd")

var selected_key: String = ""
var selected_color: Color = Color(0.2, 0.2, 0.2, 1)

@onready var _preview_rect: TextureRect = %PreviewRect
@onready var _preview_container: Control = %PreviewContainer
@onready var _hair_grid: GridContainer = %HairGrid
@onready var _color_grid: GridContainer = %ColorGrid
@onready var _title_label: Label = %TitleLabel

func _ready() -> void:
	_build_hair_options()
	_build_color_picker()

func set_data(data: Dictionary) -> void:
	if data.has("selected_hair_key") and str(data["selected_hair_key"]) != "":
		selected_key = str(data["selected_hair_key"])
	if data.has("hair_color") and data["hair_color"] is Color:
		selected_color = data["hair_color"]
	if data.has("selected_body_key"):
		_update_preview()
	_refresh_selection()

func get_data() -> Dictionary:
	return {
		"selected_hair_key": selected_key,
		"hair_color": selected_color,
	}

func _build_hair_options() -> void:
	var options: Array[Dictionary] = MOCK.get_hair_options()
	for opt: Dictionary in options:
		var btn: Button = Button.new()
		btn.custom_minimum_size = Vector2(64, 72)
		btn.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		btn.toggle_mode = true
		btn.button_group = ButtonGroup.new()
		btn.mouse_filter = Control.MOUSE_FILTER_PASS

		var placeholder: ColorRect = ColorRect.new()
		placeholder.custom_minimum_size = Vector2(48, 40)
		placeholder.color = Color("3a2a1a")
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
		btn.pressed.connect(_on_hair_selected.bind(key, btn))
		_hair_grid.add_child(btn)

		if key == selected_key:
			btn.button_pressed = true

func _build_color_picker() -> void:
	var colors: Array[Color] = MOCK.get_hair_colors()
	for c: Color in colors:
		var btn: Button = Button.new()
		btn.custom_minimum_size = Vector2(24, 24)
		btn.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		btn.toggle_mode = true
		btn.button_group = ButtonGroup.new()

		var rect: ColorRect = ColorRect.new()
		rect.custom_minimum_size = Vector2(18, 18)
		rect.color = c
		rect.mouse_filter = Control.MOUSE_FILTER_IGNORE
		btn.add_child(rect)

		btn.pressed.connect(_on_color_selected.bind(c, btn))
		_color_grid.add_child(btn)

		if c.is_equal_approx(selected_color):
			btn.button_pressed = true

func _on_hair_selected(key: String, btn: Button) -> void:
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
	_refresh_preview_color()
	emit_changed()

func _refresh_selection() -> void:
	for child: Node in _hair_grid.get_children():
		if child is Button:
			var btn: Button = child
			var label_text: String = ""
			for grandchild: Node in btn.get_children():
				if grandchild is Label:
					label_text = grandchild.text
					break
			var is_selected: bool = false
			for opt: Dictionary in MOCK.get_hair_options():
				if str(opt.get("label", "")) == label_text and str(opt.get("key", "")) == selected_key:
					is_selected = true
					break
			_apply_selected_style(btn, is_selected)

func _apply_selected_style(btn: Button, selected: bool) -> void:
	var normal: StyleBoxFlat = StyleBoxFlat.new()
	if selected:
		normal.bg_color = DesignTokens.Colors.Accent.PRIMARY
		normal.border_color = DesignTokens.Colors.Accent.PRIMARY * Color(0.85, 0.85, 0.85, 1)
	else:
		normal.bg_color = DesignTokens.Colors.Card.Dark.BG_START
		normal.border_color = DesignTokens.Colors.PanelTokens.Dark.BORDER
	normal.border_width_left = int(DesignTokens.Borders.WIDTH_THIN)
	normal.border_width_top = int(DesignTokens.Borders.WIDTH_THIN)
	normal.border_width_right = int(DesignTokens.Borders.WIDTH_THIN)
	normal.border_width_bottom = int(DesignTokens.Borders.WIDTH_THIN)
	normal.anti_aliasing = false
	btn.add_theme_stylebox_override("normal", normal)
	btn.add_theme_stylebox_override("pressed", normal)
	btn.add_theme_stylebox_override("hover", normal)

func _update_preview() -> void:
	if selected_key != "":
		var label: String = selected_key
		_preview_rect.tooltip_text = label
	_refresh_preview_color()

func _refresh_preview_color() -> void:
	_preview_container.modulate = selected_color

func _animate_option(btn: Button) -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BACK)
	btn.scale = Vector2(1.1, 1.1)
	tween.tween_property(btn, "scale", Vector2(1, 1), 0.2)

func emit_changed() -> void:
	data_changed.emit(get_data())
