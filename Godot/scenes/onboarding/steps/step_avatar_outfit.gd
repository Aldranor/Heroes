extends Control

signal data_changed(data: Dictionary)

const MOCK = preload("res://mock/avatar_options.gd")

var selected_key: String = ""

@onready var _preview_rect: TextureRect = %PreviewRect
@onready var _outfit_grid: GridContainer = %OutfitGrid
@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel

func _ready() -> void:
	_build_outfit_options()

func set_data(data: Dictionary) -> void:
	if data.has("selected_outfit_key") and str(data["selected_outfit_key"]) != "":
		selected_key = str(data["selected_outfit_key"])
	_refresh_selection()
	_update_preview()

func get_data() -> Dictionary:
	return {
		"selected_outfit_key": selected_key,
	}

func _build_outfit_options() -> void:
	var options: Array[Dictionary] = MOCK.get_outfit_options()
	for opt: Dictionary in options:
		var btn: Button = Button.new()
		btn.custom_minimum_size = Vector2(100, 100)
		btn.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		btn.toggle_mode = true
		btn.button_group = ButtonGroup.new()
		btn.mouse_filter = Control.MOUSE_FILTER_PASS

		var vbox: VBoxContainer = VBoxContainer.new()
		vbox.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		vbox.mouse_filter = Control.MOUSE_FILTER_IGNORE
		btn.add_child(vbox)

		var placeholder: ColorRect = ColorRect.new()
		placeholder.custom_minimum_size = Vector2(80, 56)
		placeholder.color = opt.get("color", Color("6b8e23"))
		placeholder.mouse_filter = Control.MOUSE_FILTER_IGNORE
		vbox.add_child(placeholder)

		var label: Label = Label.new()
		label.text = str(opt.get("label", ""))
		label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
		label.vertical_alignment = VERTICAL_ALIGNMENT_CENTER
		label.mouse_filter = Control.MOUSE_FILTER_IGNORE
		vbox.add_child(label)

		var key: String = str(opt.get("key", ""))
		btn.pressed.connect(_on_outfit_selected.bind(key, opt, btn))
		_outfit_grid.add_child(btn)

		if key == selected_key:
			btn.button_pressed = true

func _on_outfit_selected(key: String, opt: Dictionary, btn: Button) -> void:
	if not btn.button_pressed:
		return
	selected_key = key
	AudioManager.play_sfx("click", -5.0)
	_refresh_selection()
	_update_preview()
	_animate_option(btn)
	_description_label.text = str(opt.get("description", ""))
	emit_changed()

func _refresh_selection() -> void:
	for child: Node in _outfit_grid.get_children():
		if child is Button:
			var btn: Button = child
			var label_text: String = ""
			var vbox: VBoxContainer = null
			for c: Node in btn.get_children():
				if c is VBoxContainer:
					vbox = c
					break
			if vbox != null:
				for c2: Node in vbox.get_children():
					if c2 is Label:
						label_text = c2.text
						break
			var is_selected: bool = false
			for opt: Dictionary in MOCK.get_outfit_options():
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

func _animate_option(btn: Button) -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BACK)
	btn.scale = Vector2(1.05, 1.05)
	tween.tween_property(btn, "scale", Vector2(1, 1), 0.2)

func emit_changed() -> void:
	data_changed.emit(get_data())
