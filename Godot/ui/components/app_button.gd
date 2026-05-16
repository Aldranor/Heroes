extends Button
class_name AppButton

enum Variant {
	PRIMARY,
	SECONDARY,
	DANGER,
	GHOST,
	DISABLED
}

const APP_THEME: Theme = preload("res://ui/theme/app_theme.tres")

@export var variant: Variant = Variant.PRIMARY:
	set(value):
		variant = value
		if is_node_ready():
			_apply_variant()

@export var button_type: int = 0:
	set(value):
		button_type = clampi(value, 0, 2)
		variant = button_type
		if is_node_ready():
			_apply_variant()

@export var label_text: String = "Button":
	set(value):
		label_text = value
		text = label_text

@export var leading_icon: Texture2D:
	set(value):
		leading_icon = value
		icon = leading_icon

func _ready() -> void:
	theme = APP_THEME
	text = label_text
	icon = leading_icon
	custom_minimum_size = Vector2(0, 44)
	size_flags_horizontal = Control.SIZE_EXPAND_FILL
	focus_mode = Control.FOCUS_ALL
	clip_text = true
	_apply_variant()

func _apply_variant() -> void:
	_clear_theme_overrides()
	disabled = false
	theme_type_variation = ""

	match variant:
		Variant.PRIMARY:
			return
		Variant.SECONDARY:
			theme_type_variation = "ButtonSecondary"
		Variant.DANGER:
			_apply_danger_variant()
		Variant.GHOST:
			_apply_ghost_variant()
		Variant.DISABLED:
			disabled = true

func _apply_danger_variant() -> void:
	var normal := _flat(Color(220.0 / 255.0, 38.0 / 255.0, 38.0 / 255.0, 0.92), Color.TRANSPARENT, 0)
	var hover := _flat(Color(239.0 / 255.0, 68.0 / 255.0, 68.0 / 255.0, 0.95), Color.TRANSPARENT, 0)
	var pressed := _flat(Color(185.0 / 255.0, 28.0 / 255.0, 28.0 / 255.0, 0.95), Color.TRANSPARENT, 0)
	var disabled_style := _flat(Color(1, 1, 1, 0.06), Color.TRANSPARENT, 0)
	_set_button_styles(normal, hover, pressed, disabled_style)
	add_theme_color_override("font_color", Color("fff7ed"))
	add_theme_color_override("font_hover_color", Color("ffffff"))
	add_theme_color_override("font_pressed_color", Color("fff7ed"))
	add_theme_color_override("font_disabled_color", DesignTokens.Colors.Text.Disabled.DARK)

func _apply_ghost_variant() -> void:
	var border := DesignTokens.Colors.Card.Dark.BORDER
	var hover_border := DesignTokens.Colors.Card.Dark.BORDER_HOVER
	var normal := _flat(Color.TRANSPARENT, border, int(DesignTokens.Borders.WIDTH_THIN))
	var hover := _flat(Color(1, 1, 1, 0.04), hover_border, int(DesignTokens.Borders.WIDTH_THIN))
	var pressed := _flat(Color(1, 1, 1, 0.02), border, int(DesignTokens.Borders.WIDTH_THIN))
	var disabled_style := _flat(Color.TRANSPARENT, Color(1, 1, 1, 0.04), int(DesignTokens.Borders.WIDTH_THIN))
	_set_button_styles(normal, hover, pressed, disabled_style)
	add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)
	add_theme_color_override("font_hover_color", DesignTokens.Colors.Accent.PRIMARY_HOVER)
	add_theme_color_override("font_pressed_color", DesignTokens.Colors.Text.Primary.DARK)
	add_theme_color_override("font_disabled_color", DesignTokens.Colors.Text.Disabled.DARK)

func _set_button_styles(normal: StyleBoxFlat, hover: StyleBoxFlat, pressed: StyleBoxFlat, disabled_style: StyleBoxFlat) -> void:
	add_theme_stylebox_override("normal", normal)
	add_theme_stylebox_override("hover", hover)
	add_theme_stylebox_override("pressed", pressed)
	add_theme_stylebox_override("focus", hover)
	add_theme_stylebox_override("disabled", disabled_style)

func _flat(bg: Color, border: Color, border_width: int) -> StyleBoxFlat:
	var sb := StyleBoxFlat.new()
	sb.bg_color = bg
	sb.border_color = border
	sb.border_width_left = border_width
	sb.border_width_top = border_width
	sb.border_width_right = border_width
	sb.border_width_bottom = border_width
	sb.corner_radius_top_left = int(DesignTokens.Radius.CHIP)
	sb.corner_radius_top_right = int(DesignTokens.Radius.CHIP)
	sb.corner_radius_bottom_left = int(DesignTokens.Radius.CHIP)
	sb.corner_radius_bottom_right = int(DesignTokens.Radius.CHIP)
	sb.content_margin_left = DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H
	sb.content_margin_right = DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H
	sb.content_margin_top = DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V
	sb.content_margin_bottom = DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V
	return sb

func _clear_theme_overrides() -> void:
	for style_name: StringName in [&"normal", &"hover", &"pressed", &"focus", &"disabled"]:
		remove_theme_stylebox_override(style_name)
	for color_name: StringName in [&"font_color", &"font_hover_color", &"font_pressed_color", &"font_disabled_color"]:
		remove_theme_color_override(color_name)
