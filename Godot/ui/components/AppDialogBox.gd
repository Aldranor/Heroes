extends PanelContainer
class_name AppDialogBox

signal continued()

@export var character_name: String = "":
	set(value):
		character_name = value
		if is_node_ready():
			_refresh()

@export var dialog_text: String = "":
	set(value):
		dialog_text = value
		if is_node_ready():
			_refresh()

@export var show_continue_button: bool = true:
	set(value):
		show_continue_button = value
		if is_node_ready():
			_refresh()

@export var typewriter_enabled: bool = true:
	set(value):
		typewriter_enabled = value
		if is_node_ready():
			_refresh()

@export var chars_per_second: float = 45.0

@onready var _header: HBoxContainer = %Header
@onready var _avatar: TextureRect = %SpeakerAvatar
@onready var _speaker_name: Label = %SpeakerName
@onready var _dialog_label: Label = %DialogLabel
@onready var _footer: HBoxContainer = %Footer
@onready var _continue_button: Button = %ContinueButton
@onready var _next_indicator: Label = %NextIndicator
@onready var _type_timer: Timer = %TypeTimer
@onready var _blink_timer: Timer = %BlinkTimer

var _full_text: String = ""
var _visible_count: int = 0
var _is_typing: bool = false


func _ready() -> void:
	_stylize()
	_continue_button.pressed.connect(_on_continue_pressed)
	_type_timer.timeout.connect(_on_type_timer_timeout)
	_blink_timer.timeout.connect(_on_blink_timer_timeout)
	_refresh()


func _stylize() -> void:
	remove_theme_stylebox_override("panel")
	var sb := StyleBoxFlat.new()
	sb.bg_color = DesignTokens.Colors.PanelTokens.Dark.BG_START
	sb.border_color = DesignTokens.Colors.PanelTokens.Dark.BORDER
	var bw := int(DesignTokens.Borders.WIDTH_THIN)
	sb.border_width_left = bw
	sb.border_width_top = bw
	sb.border_width_right = bw
	sb.border_width_bottom = bw
	sb.set_corner_radius_all(int(round(DesignTokens.Radius.PANEL)))
	sb.content_margin_left = DesignTokens.Spacing.LG
	sb.content_margin_top = DesignTokens.Spacing.LG
	sb.content_margin_right = DesignTokens.Spacing.LG
	sb.content_margin_bottom = DesignTokens.Spacing.LG
	sb.anti_aliasing = false
	add_theme_stylebox_override("panel", sb)

	_speaker_name.add_theme_color_override("font_color", DesignTokens.Colors.Accent.PRIMARY)
	_speaker_name.add_theme_constant_override("line_spacing", 1)
	_speaker_name.add_theme_constant_override("shadow_offset_x", 0)
	_speaker_name.add_theme_constant_override("shadow_offset_y", 0)
	_speaker_name.add_theme_constant_override("outline_size", 0)
	_speaker_name.add_theme_constant_override("shadow_outline_size", 0)

	_next_indicator.add_theme_color_override("font_color", DesignTokens.Colors.Accent.PRIMARY)

	_continue_button.add_theme_color_override("font_color", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	_continue_button.add_theme_stylebox_override("normal", _build_button_style(DesignTokens.Colors.Accent.PRIMARY))
	_continue_button.add_theme_stylebox_override("hover", _build_button_style(DesignTokens.Colors.Accent.PRIMARY_HOVER))
	_continue_button.add_theme_stylebox_override("pressed", _build_button_style(DesignTokens.Colors.Accent.PRIMARY.darkened(0.1)))

	_header.add_theme_constant_override("separation", DesignTokens.Spacing.MD)
	_footer.add_theme_constant_override("separation", DesignTokens.Spacing.SM)


static func _build_button_style(bg: Color) -> StyleBoxFlat:
	var sb := StyleBoxFlat.new()
	sb.bg_color = bg
	sb.set_corner_radius_all(int(round(DesignTokens.Radius.PILL)))
	sb.content_margin_left = DesignTokens.Spacing.XL
	sb.content_margin_top = DesignTokens.Spacing.SM
	sb.content_margin_right = DesignTokens.Spacing.XL
	sb.content_margin_bottom = DesignTokens.Spacing.SM
	sb.anti_aliasing = false
	return sb


func _refresh() -> void:
	var has_name := not character_name.is_empty()
	_header.visible = has_name
	_speaker_name.text = "[ %s ]" % character_name.to_upper()
	_avatar.visible = has_name

	_full_text = dialog_text
	_visible_count = 0

	if typewriter_enabled and not _full_text.is_empty():
		_start_typing()
	else:
		_dialog_label.text = _full_text
		_finish_typing()

	_continue_button.visible = show_continue_button and not _is_typing
	if show_continue_button:
		_continue_button.text = tr("Continuer") if not _is_typing else tr("Suivant")


func _start_typing() -> void:
	_is_typing = true
	_dialog_label.text = ""
	_next_indicator.visible = false
	_blink_timer.stop()
	var safe_speed := maxf(chars_per_second, 1.0)
	_type_timer.wait_time = 1.0 / safe_speed
	_type_timer.start()


func _finish_typing() -> void:
	_is_typing = false
	_type_timer.stop()
	if _full_text.length() <= 0:
		_dialog_label.text = ""
		return
	_dialog_label.text = _full_text
	if show_continue_button:
		_continue_button.visible = true
		_continue_button.text = tr("Continuer")
		_next_indicator.visible = false
	else:
		_next_indicator.visible = true
		_blink_timer.start()


func _on_continue_pressed() -> void:
	if typewriter_enabled and _is_typing:
		_finish_typing()
		return
	emit_signal("continued")


func _on_type_timer_timeout() -> void:
	if not _is_typing:
		return
	_visible_count += 1
	_dialog_label.text = _full_text.substr(0, _visible_count)
	if _visible_count >= _full_text.length():
		_finish_typing()


func _on_blink_timer_timeout() -> void:
	_next_indicator.visible = not _next_indicator.visible
