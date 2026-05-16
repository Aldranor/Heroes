extends PanelContainer
class_name AppCard

enum Variant {
	NORMAL,
	HIGHLIGHTED,
	MUTED
}

signal pressed()

@export var variant: Variant = Variant.NORMAL:
	set(value):
		variant = value
		_apply_variant()

@export var card_title: String = "":
	set(value):
		card_title = value
		if is_node_ready():
			_update_header()

@export var card_subtitle: String = "":
	set(value):
		card_subtitle = value
		if is_node_ready():
			_update_header()

@export var interactable: bool = false:
	set(value):
		interactable = value
		if is_node_ready():
			_update_interactable()

@onready var _layout: VBoxContainer = %Layout
@onready var _header: VBoxContainer = %Header
@onready var _title_label: Label = %TitleLabel
@onready var _subtitle_label: Label = %SubtitleLabel
@onready var _content: VBoxContainer = %Content

var _anim_tween: Tween
var _pressed_style: StyleBoxFlat


func _ready() -> void:
	_layout.add_theme_constant_override("separation", DesignTokens.Spacing.MD)
	_header.add_theme_constant_override("separation", DesignTokens.Spacing.XS)
	_apply_variant()
	_update_header()
	_update_interactable()


func _update_header() -> void:
	var has_title := not card_title.is_empty()
	var has_subtitle := not card_subtitle.is_empty()
	_header.visible = has_title or has_subtitle
	_title_label.text = card_title
	_title_label.visible = has_title
	_subtitle_label.text = card_subtitle
	_subtitle_label.visible = has_subtitle


func _update_interactable() -> void:
	mouse_filter = Control.MOUSE_FILTER_STOP if interactable else Control.MOUSE_FILTER_IGNORE
	if not interactable:
		mouse_default_cursor_shape = Control.CURSOR_ARROW
		focus_mode = Control.FOCUS_NONE
		if gui_input.is_connected(_on_gui_input):
			gui_input.disconnect(_on_gui_input)
		return
	mouse_default_cursor_shape = Control.CURSOR_POINTING_HAND
	focus_mode = Control.FOCUS_ALL
	gui_input.connect(_on_gui_input)


func _on_gui_input(event: InputEvent) -> void:
	if event is InputEventScreenTouch or event is InputEventMouseButton:
		var pressed: bool
		if event is InputEventScreenTouch:
			pressed = event.pressed
		else:
			pressed = event.pressed and event.button_index == MOUSE_BUTTON_LEFT
		if pressed:
			_animate_press()
		else:
			_animate_release()


func _animate_press() -> void:
	_pressed_style = _build_pressed_style()
	remove_theme_stylebox_override("panel")
	add_theme_stylebox_override("panel", _pressed_style)


func _animate_release() -> void:
	if _anim_tween != null:
		_anim_tween.kill()
	_anim_tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_CUBIC)
	_anim_tween.tween_callback(_apply_variant)
	_anim_tween.tween_callback(emit_signal.bind("pressed"))


func _apply_variant() -> void:
	if not is_node_ready():
		return
	remove_theme_stylebox_override("panel")
	add_theme_stylebox_override("panel", _build_style())


func _build_style() -> StyleBoxFlat:
	var sb := StyleBoxFlat.new()
	var bw := int(DesignTokens.Borders.WIDTH_THIN)
	match variant:
		Variant.NORMAL:
			sb.bg_color = DesignTokens.Colors.Card.Dark.BG_START
			sb.border_color = DesignTokens.Colors.Card.Dark.BORDER
			sb.set_corner_radius_all(int(round(DesignTokens.Radius.CARD)))
		Variant.HIGHLIGHTED:
			sb.bg_color = DesignTokens.Colors.Card.Dark.BG_START
			sb.border_color = DesignTokens.Colors.Card.Dark.BORDER_SELECTED
			sb.set_corner_radius_all(int(round(DesignTokens.Radius.CARD)))
		Variant.MUTED:
			var bg := DesignTokens.Colors.Card.Dark.BG_START
			bg.a = 0.55
			sb.bg_color = bg
			var brd := DesignTokens.Colors.PanelTokens.Dark.BORDER
			brd.a = 0.5
			sb.border_color = brd
			sb.set_corner_radius_all(int(round(DesignTokens.Radius.CARD_SM)))
	sb.border_width_left = bw
	sb.border_width_top = bw
	sb.border_width_right = bw
	sb.border_width_bottom = bw
	sb.content_margin_left = DesignTokens.Spacing.LG
	sb.content_margin_top = DesignTokens.Spacing.LG
	sb.content_margin_right = DesignTokens.Spacing.LG
	sb.content_margin_bottom = DesignTokens.Spacing.LG
	sb.anti_aliasing = false
	return sb


func _build_pressed_style() -> StyleBoxFlat:
	var sb := _build_style()
	var hl := DesignTokens.Colors.Accent.PRIMARY
	hl.a = 0.08
	sb.bg_color = hl
	return sb


## Returns the content container. Add child controls here.
func get_content() -> VBoxContainer:
	return _content
