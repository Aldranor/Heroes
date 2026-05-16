@tool
extends Node
class_name ThemeFactory

## Programmatic theme builder driven by DesignTokens.
## Use in editor to regenerate app_theme.tres, or at runtime for theme switching.
##
## Editor usage:
##   @tool
##   func _ready():
##       if Engine.is_editor_hint():
##           ThemeFactory.save_theme("res://ui/theme/app_theme.tres", ThemeFactory.build_dark_theme())
##
## Runtime usage (autoload):
##   get_tree().root.theme = ThemeFactory.build_dark_theme()


# --- Public builders ---

static func build_dark_theme(font_size: int = DesignTokens.Typography.Size.BODY) -> Theme:
	var t := Theme.new()
	t.default_font_size = font_size
	t.default_base_scale = 1.0

	_apply_button_primary(t)
	_apply_button_secondary(t)
	_apply_label(t)
	_apply_panel(t)
	_apply_progress_bar(t)
	_apply_line_edit(t)
	_apply_texture_button(t)
	_apply_checkbox(t)
	_apply_palette(t)
	return t


static func build_light_theme(font_size: int = DesignTokens.Typography.Size.BODY) -> Theme:
	var t := Theme.new()
	t.default_font_size = font_size
	t.default_base_scale = 1.0

	_apply_button_primary_light(t)
	_apply_button_secondary_light(t)
	_apply_label_light(t)
	_apply_panel_light(t)
	_apply_progress_bar_light(t)
	_apply_line_edit_light(t)
	_apply_texture_button_light(t)
	_apply_checkbox_light(t)
	_apply_palette_light(t)
	return t


# --- Save helper ---

static func save_theme(path: String, theme: Theme) -> void:
	var err := ResourceSaver.save(theme, path)
	if err != OK:
		push_error("ThemeFactory: failed to save theme to %s (error %d)" % [path, err])
	else:
		print_rich("[color=green]Theme saved:[/color] %s" % path)


# =========================================================================
# Dark theme — private builders
# =========================================================================

static func _apply_button_primary(t: Theme) -> void:
	var ui_font := _ui_font()
	var normal := _flat(
		DesignTokens.Colors.Accent.PRIMARY,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	var hover := _flat(
		DesignTokens.Colors.Accent.PRIMARY_HOVER,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	var pressed := _flat(
		DesignTokens.Colors.Accent.PRIMARY.darkened(0.1),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	var disabled := _flat(
		Color(1, 1, 1, 0.06),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	t.set_stylebox("normal", "Button", normal)
	t.set_stylebox("hover", "Button", hover)
	t.set_stylebox("pressed", "Button", pressed)
	t.set_stylebox("focus", "Button", hover)
	t.set_stylebox("disabled", "Button", disabled)
	t.set_color("font_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_hover_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_pressed_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_disabled_color", "Button", DesignTokens.Colors.Text.Disabled.DARK)
	t.set_color("font_focus_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_constant("h_separation", "Button", DesignTokens.Spacing.SM)
	t.set_font("font", "Button", ui_font)
	t.set_font_size("font_size", "Button", DesignTokens.Typography.Size.BODY)


static func _apply_button_secondary(t: Theme) -> void:
	var ui_font := _ui_font()
	# Ghost / outline button — used via theme type variation
	var normal := _flat(
		Color.TRANSPARENT,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Card.Dark.BORDER
	)
	var hover := _flat(
		Color(1, 1, 1, 0.06),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Card.Dark.BORDER_HOVER
	)
	var pressed := _flat(
		Color(1, 1, 1, 0.04),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Card.Dark.BORDER
	)
	var disabled := _flat(
		Color.TRANSPARENT,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		Color(1, 1, 1, 0.04)
	)
	var type := "ButtonSecondary"
	t.set_type_variation(type, "Button")
	t.set_stylebox("normal", type, normal)
	t.set_stylebox("hover", type, hover)
	t.set_stylebox("pressed", type, pressed)
	t.set_stylebox("focus", type, hover)
	t.set_stylebox("disabled", type, disabled)
	t.set_color("font_color", type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_hover_color", type, DesignTokens.Colors.Accent.PRIMARY)
	t.set_color("font_pressed_color", type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_disabled_color", type, DesignTokens.Colors.Text.Disabled.DARK)
	t.set_color("font_focus_color", type, DesignTokens.Colors.Accent.PRIMARY)
	t.set_constant("h_separation", type, DesignTokens.Spacing.SM)
	t.set_font("font", type, ui_font)
	t.set_font_size("font_size", type, DesignTokens.Typography.Size.BODY)


static func _apply_label(t: Theme) -> void:
	var ui_font := _ui_font()
	var display_font := _display_font()
	t.set_color("font_color", "Label", DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_outline_color", "Label", Color(0, 0, 0, 0))
	t.set_font("font", "Label", ui_font)
	t.set_font_size("font_size", "Label", DesignTokens.Typography.Size.BODY)

	var secondary_type := "LabelSecondary"
	t.set_type_variation(secondary_type, "Label")
	t.set_color("font_color", secondary_type, DesignTokens.Colors.Text.Secondary.DARK)
	t.set_font("font", secondary_type, ui_font)
	t.set_font_size("font_size", secondary_type, DesignTokens.Typography.Size.BODY)

	var muted_type := "LabelMuted"
	t.set_type_variation(muted_type, "Label")
	t.set_color("font_color", muted_type, DesignTokens.Colors.Text.Muted.DARK)
	t.set_font("font", muted_type, ui_font)
	t.set_font_size("font_size", muted_type, DesignTokens.Typography.Size.BODY_SM)

	var heading_type := "LabelHeading"
	t.set_type_variation(heading_type, "Label")
	t.set_color("font_color", heading_type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_constant("line_spacing", heading_type, 0)
	t.set_font_size("font_size", heading_type, DesignTokens.Typography.Size.HEADING_LG)

	var eyebrow_type := "LabelEyebrow"
	t.set_type_variation(eyebrow_type, "Label")
	t.set_color("font_color", eyebrow_type, DesignTokens.Colors.Text.Secondary.DARK)
	t.set_font("font", eyebrow_type, ui_font)
	t.set_font_size("font_size", eyebrow_type, DesignTokens.Typography.Size.LABEL)

	var brand_type := "LabelBrand"
	t.set_type_variation(brand_type, "Label")
	t.set_color("font_color", brand_type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_font("font", brand_type, ui_font)
	t.set_font_size("font_size", brand_type, DesignTokens.Typography.Size.BODY_SM)

	var secondary_lg_type := "LabelSecondaryLg"
	t.set_type_variation(secondary_lg_type, "Label")
	t.set_color("font_color", secondary_lg_type, DesignTokens.Colors.Text.Secondary.DARK)
	t.set_font("font", secondary_lg_type, ui_font)
	t.set_font_size("font_size", secondary_lg_type, DesignTokens.Typography.Size.BODY_LG)

	var primary_sm_type := "LabelPrimarySm"
	t.set_type_variation(primary_sm_type, "Label")
	t.set_color("font_color", primary_sm_type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_font("font", primary_sm_type, ui_font)
	t.set_font_size("font_size", primary_sm_type, DesignTokens.Typography.Size.BODY_SM)

	var primary_lg_type := "LabelPrimaryLg"
	t.set_type_variation(primary_lg_type, "Label")
	t.set_color("font_color", primary_lg_type, DesignTokens.Colors.Text.Primary.DARK)
	t.set_font("font", primary_lg_type, ui_font)
	t.set_font_size("font_size", primary_lg_type, DesignTokens.Typography.Size.BODY_LG)

	var display_lg_type := "LabelDisplayLg"
	t.set_type_variation(display_lg_type, "Label")
	t.set_font("font", display_lg_type, display_font)
	t.set_font_size("font_size", display_lg_type, DesignTokens.Typography.Size.HEADING_LG)
	t.set_color("font_color", display_lg_type, DesignTokens.Colors.Text.Primary.DARK)

	var display_hero_type := "LabelDisplayHero"
	t.set_type_variation(display_hero_type, "Label")
	t.set_font("font", display_hero_type, display_font)
	t.set_font_size("font_size", display_hero_type, DesignTokens.Typography.Size.HERO)
	t.set_color("font_color", display_hero_type, DesignTokens.Colors.Text.Primary.DARK)

	t.set_font("font", "LinkButton", ui_font)
	t.set_font_size("font_size", "LinkButton", DesignTokens.Typography.Size.BODY_SM)
	t.set_color("font_color", "LinkButton", DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_hover_color", "LinkButton", DesignTokens.Colors.Accent.PRIMARY)


static func _apply_panel(t: Theme) -> void:
	var style := _flat(
		DesignTokens.Colors.PanelTokens.Dark.BG_START,
		DesignTokens.Radius.PANEL,
		DesignTokens.Spacing.LG,
		DesignTokens.Spacing.LG,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.PanelTokens.Dark.BORDER
	)
	t.set_stylebox("panel", "Panel", style)
	t.set_stylebox("panel", "PanelContainer", style)


static func _apply_progress_bar(t: Theme) -> void:
	var bg := _flat(
		DesignTokens.Colors.ProgressBarTokens.TRACK_BG,
		DesignTokens.Radius.PILL,
		0, 0, 0, Color.TRANSPARENT
	)
	var fill := _flat(
		DesignTokens.Colors.ProgressBarTokens.FILL_BG,
		DesignTokens.Radius.PILL,
		0, 0, 0, Color.TRANSPARENT
	)
	t.set_stylebox("background", "ProgressBar", bg)
	t.set_stylebox("fill", "ProgressBar", fill)
	t.set_color("font_color", "ProgressBar", DesignTokens.Colors.Text.Primary.DARK)


static func _apply_line_edit(t: Theme) -> void:
	var normal := _flat(
		DesignTokens.Colors.InputTokens.Dark.BG,
		DesignTokens.Radius.FORM,
		DesignTokens.Spacing.MD,
		DesignTokens.Spacing.MD,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.InputTokens.Dark.BORDER
	)
	var focus := _flat(
		DesignTokens.Colors.InputTokens.Dark.BG,
		DesignTokens.Radius.FORM,
		DesignTokens.Spacing.MD,
		DesignTokens.Spacing.MD,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Accent.PRIMARY
	)
	var readonly := _flat(
		DesignTokens.Colors.InputTokens.Dark.BG.darkened(0.03),
		DesignTokens.Radius.FORM,
		DesignTokens.Spacing.MD,
		DesignTokens.Spacing.MD,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.InputTokens.Dark.BORDER
	)
	t.set_stylebox("normal", "LineEdit", normal)
	t.set_stylebox("focus", "LineEdit", focus)
	t.set_stylebox("read_only", "LineEdit", readonly)
	t.set_color("font_color", "LineEdit", DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_placeholder_color", "LineEdit", DesignTokens.Colors.Text.Muted.DARK)
	t.set_color("caret_color", "LineEdit", DesignTokens.Colors.Accent.PRIMARY)
	t.set_color("selection_color", "LineEdit", DesignTokens.Colors.Accent.PRIMARY.darkened(0.5))
	t.set_constant("minimum_character_width", "LineEdit", 4)


static func _apply_texture_button(t: Theme) -> void:
	var normal := _flat(Color.TRANSPARENT, 0, 0, 0, 0, Color.TRANSPARENT)
	var hover := _flat(Color(1, 1, 1, 0.06), DesignTokens.Radius.SM, 0, 0, 0, Color.TRANSPARENT)
	var pressed := _flat(Color(1, 1, 1, 0.04), DesignTokens.Radius.SM, 0, 0, 0, Color.TRANSPARENT)
	t.set_stylebox("normal", "TextureButton", normal)
	t.set_stylebox("hover", "TextureButton", hover)
	t.set_stylebox("pressed", "TextureButton", pressed)
	t.set_stylebox("focus", "TextureButton", hover)
	t.set_stylebox("disabled", "TextureButton", normal)


static func _apply_checkbox(t: Theme) -> void:
	t.set_color("font_color", "CheckBox", DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("font_hover_color", "CheckBox", DesignTokens.Colors.Accent.PRIMARY)
	t.set_color("font_pressed_color", "CheckBox", DesignTokens.Colors.Text.Primary.DARK)
	t.set_constant("h_separation", "CheckBox", DesignTokens.Spacing.SM)


static func _apply_palette(t: Theme) -> void:
	t.set_color("accent_gold", "Palette", DesignTokens.Colors.Accent.PRIMARY)
	t.set_color("accent_green", "Palette", DesignTokens.Colors.Specialization.SAFETY)
	t.set_color("accent_red", "Palette", DesignTokens.Colors.Specialization.DANGERS)
	t.set_color("accent_blue", "Palette", DesignTokens.Colors.Specialization.SPEED)
	t.set_color("accent_purple", "Palette", DesignTokens.Colors.Specialization.MIXED)
	t.set_color("accent_orange", "Palette", DesignTokens.Colors.Specialization.PRIORITY)
	t.set_color("background_primary", "Palette", DesignTokens.Colors.Shell.Dark.BG_START)
	t.set_color("background_secondary", "Palette", DesignTokens.Colors.PanelTokens.Dark.BG_START)
	t.set_color("background_tertiary", "Palette", DesignTokens.Colors.Card.Dark.BG_START)
	t.set_color("border_default", "Palette", DesignTokens.Colors.PanelTokens.Dark.BORDER)
	t.set_color("text_primary", "Palette", DesignTokens.Colors.Text.Primary.DARK)
	t.set_color("text_secondary", "Palette", DesignTokens.Colors.Text.Secondary.DARK)


# =========================================================================
# Light theme — private builders
# =========================================================================

static func _apply_button_primary_light(t: Theme) -> void:
	var ui_font := _ui_font()
	var normal := _flat(
		DesignTokens.Colors.Accent.PRIMARY,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	var hover := _flat(
		DesignTokens.Colors.Accent.PRIMARY_HOVER,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	var pressed := _flat(
		DesignTokens.Colors.Accent.PRIMARY.darkened(0.1),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		0, Color.TRANSPARENT
	)
	t.set_stylebox("normal", "Button", normal)
	t.set_stylebox("hover", "Button", hover)
	t.set_stylebox("pressed", "Button", pressed)
	t.set_stylebox("focus", "Button", hover)
	t.set_color("font_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_hover_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_pressed_color", "Button", DesignTokens.Colors.Accent.PRIMARY_TEXT)
	t.set_color("font_disabled_color", "Button", DesignTokens.Colors.Text.Disabled.LIGHT)
	t.set_font("font", "Button", ui_font)
	t.set_font_size("font_size", "Button", DesignTokens.Typography.Size.BODY)


static func _apply_button_secondary_light(t: Theme) -> void:
	var ui_font := _ui_font()
	var normal := _flat(
		Color.TRANSPARENT,
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.PanelTokens.Light.BORDER
	)
	var hover := _flat(
		Color(0, 0, 0, 0.04),
		DesignTokens.Radius.CHIP,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H,
		DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Card.Light.BORDER_HOVER
	)
	var type := "ButtonSecondary"
	t.set_type_variation(type, "Button")
	t.set_stylebox("normal", type, normal)
	t.set_stylebox("hover", type, hover)
	t.set_stylebox("pressed", type, _flat(Color(0, 0, 0, 0.06), DesignTokens.Radius.CHIP, DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_H, DesignTokens.ComponentSizes.ButtonTokens.LG_PAD_V, int(DesignTokens.Borders.WIDTH_THIN), DesignTokens.Colors.PanelTokens.Light.BORDER))
	t.set_stylebox("focus", type, hover)
	t.set_color("font_color", type, DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_color("font_hover_color", type, DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_font("font", type, ui_font)
	t.set_font_size("font_size", type, DesignTokens.Typography.Size.BODY)


static func _apply_label_light(t: Theme) -> void:
	var ui_font := _ui_font()
	var display_font := _display_font()
	t.set_color("font_color", "Label", DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_font("font", "Label", ui_font)
	t.set_font_size("font_size", "Label", DesignTokens.Typography.Size.BODY)
	var secondary_type := "LabelSecondary"
	t.set_type_variation(secondary_type, "Label")
	t.set_color("font_color", secondary_type, DesignTokens.Colors.Text.Secondary.LIGHT)
	t.set_font("font", secondary_type, ui_font)
	t.set_font_size("font_size", secondary_type, DesignTokens.Typography.Size.BODY)
	var muted_type := "LabelMuted"
	t.set_type_variation(muted_type, "Label")
	t.set_color("font_color", muted_type, DesignTokens.Colors.Text.Muted.LIGHT)
	t.set_font("font", muted_type, ui_font)
	t.set_font_size("font_size", muted_type, DesignTokens.Typography.Size.BODY_SM)

	var eyebrow_type := "LabelEyebrow"
	t.set_type_variation(eyebrow_type, "Label")
	t.set_color("font_color", eyebrow_type, DesignTokens.Colors.Text.Secondary.LIGHT)
	t.set_font("font", eyebrow_type, ui_font)
	t.set_font_size("font_size", eyebrow_type, DesignTokens.Typography.Size.LABEL)

	var brand_type := "LabelBrand"
	t.set_type_variation(brand_type, "Label")
	t.set_color("font_color", brand_type, DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_font("font", brand_type, ui_font)
	t.set_font_size("font_size", brand_type, DesignTokens.Typography.Size.BODY_SM)

	var secondary_lg_type := "LabelSecondaryLg"
	t.set_type_variation(secondary_lg_type, "Label")
	t.set_color("font_color", secondary_lg_type, DesignTokens.Colors.Text.Secondary.LIGHT)
	t.set_font("font", secondary_lg_type, ui_font)
	t.set_font_size("font_size", secondary_lg_type, DesignTokens.Typography.Size.BODY_LG)

	var primary_sm_type := "LabelPrimarySm"
	t.set_type_variation(primary_sm_type, "Label")
	t.set_color("font_color", primary_sm_type, DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_font("font", primary_sm_type, ui_font)
	t.set_font_size("font_size", primary_sm_type, DesignTokens.Typography.Size.BODY_SM)

	var primary_lg_type := "LabelPrimaryLg"
	t.set_type_variation(primary_lg_type, "Label")
	t.set_color("font_color", primary_lg_type, DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_font("font", primary_lg_type, ui_font)
	t.set_font_size("font_size", primary_lg_type, DesignTokens.Typography.Size.BODY_LG)

	var display_lg_type := "LabelDisplayLg"
	t.set_type_variation(display_lg_type, "Label")
	t.set_font("font", display_lg_type, display_font)
	t.set_font_size("font_size", display_lg_type, DesignTokens.Typography.Size.HEADING_LG)
	t.set_color("font_color", display_lg_type, DesignTokens.Colors.Text.Primary.LIGHT)

	var display_hero_type := "LabelDisplayHero"
	t.set_type_variation(display_hero_type, "Label")
	t.set_font("font", display_hero_type, display_font)
	t.set_font_size("font_size", display_hero_type, DesignTokens.Typography.Size.HERO)
	t.set_color("font_color", display_hero_type, DesignTokens.Colors.Text.Primary.LIGHT)

	t.set_font("font", "LinkButton", ui_font)
	t.set_font_size("font_size", "LinkButton", DesignTokens.Typography.Size.BODY_SM)
	t.set_color("font_color", "LinkButton", DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_color("font_hover_color", "LinkButton", DesignTokens.Colors.Text.Secondary.LIGHT)


static func _apply_panel_light(t: Theme) -> void:
	var style := _flat(
		DesignTokens.Colors.PanelTokens.Light.BG,
		DesignTokens.Radius.PANEL,
		DesignTokens.Spacing.LG,
		DesignTokens.Spacing.LG,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.PanelTokens.Light.BORDER
	)
	t.set_stylebox("panel", "Panel", style)
	t.set_stylebox("panel", "PanelContainer", style)


static func _apply_progress_bar_light(t: Theme) -> void:
	var bg := _flat(
		DesignTokens.Colors.ZincScale.Z200,
		DesignTokens.Radius.PILL,
		0, 0, 0, Color.TRANSPARENT
	)
	var fill := _flat(
		DesignTokens.Colors.ProgressBarTokens.FILL_BG,
		DesignTokens.Radius.PILL,
		0, 0, 0, Color.TRANSPARENT
	)
	t.set_stylebox("background", "ProgressBar", bg)
	t.set_stylebox("fill", "ProgressBar", fill)
	t.set_color("font_color", "ProgressBar", DesignTokens.Colors.Text.Primary.LIGHT)


static func _apply_line_edit_light(t: Theme) -> void:
	var normal := _flat(
		DesignTokens.Colors.InputTokens.Light.BG,
		DesignTokens.Radius.FORM,
		DesignTokens.Spacing.MD,
		DesignTokens.Spacing.MD,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.InputTokens.Light.BORDER
	)
	var focus := _flat(
		DesignTokens.Colors.InputTokens.Light.BG,
		DesignTokens.Radius.FORM,
		DesignTokens.Spacing.MD,
		DesignTokens.Spacing.MD,
		int(DesignTokens.Borders.WIDTH_THIN),
		DesignTokens.Colors.Accent.PRIMARY
	)
	t.set_stylebox("normal", "LineEdit", normal)
	t.set_stylebox("focus", "LineEdit", focus)
	t.set_color("font_color", "LineEdit", DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_color("font_placeholder_color", "LineEdit", DesignTokens.Colors.InputTokens.Light.PLACEHOLDER)
	t.set_color("caret_color", "LineEdit", DesignTokens.Colors.Accent.PRIMARY)


static func _apply_texture_button_light(t: Theme) -> void:
	var normal := _flat(Color.TRANSPARENT, 0, 0, 0, 0, Color.TRANSPARENT)
	var hover := _flat(Color(0, 0, 0, 0.04), DesignTokens.Radius.SM, 0, 0, 0, Color.TRANSPARENT)
	t.set_stylebox("normal", "TextureButton", normal)
	t.set_stylebox("hover", "TextureButton", hover)
	t.set_stylebox("pressed", "TextureButton", _flat(Color(0, 0, 0, 0.06), DesignTokens.Radius.SM, 0, 0, 0, Color.TRANSPARENT))
	t.set_stylebox("focus", "TextureButton", hover)


static func _apply_checkbox_light(t: Theme) -> void:
	t.set_color("font_color", "CheckBox", DesignTokens.Colors.Text.Primary.LIGHT)
	t.set_color("font_hover_color", "CheckBox", DesignTokens.Colors.Accent.PRIMARY)


static func _apply_palette_light(t: Theme) -> void:
	t.set_color("accent_gold", "Palette", DesignTokens.Colors.Accent.PRIMARY)
	t.set_color("accent_green", "Palette", DesignTokens.Colors.Specialization.SAFETY)
	t.set_color("accent_red", "Palette", DesignTokens.Colors.Specialization.DANGERS)
	t.set_color("accent_blue", "Palette", DesignTokens.Colors.Specialization.SPEED)
	t.set_color("accent_purple", "Palette", DesignTokens.Colors.Specialization.MIXED)
	t.set_color("accent_orange", "Palette", DesignTokens.Colors.Specialization.PRIORITY)
	t.set_color("background_primary", "Palette", DesignTokens.Colors.Shell.Light.BG_START)
	t.set_color("background_secondary", "Palette", DesignTokens.Colors.PanelTokens.Light.BG)
	t.set_color("background_tertiary", "Palette", DesignTokens.Colors.Card.Light.BG)
	t.set_color("border_default", "Palette", DesignTokens.Colors.PanelTokens.Light.BORDER)


# =========================================================================
# Shared helpers
# =========================================================================

## Creates a flat (no shadow / no texture / no gradient) StyleBoxFlat.
static func _flat(
	bg: Color,
	radius: float,
	pad_h: float,
	pad_v: float,
	border_w: int,
	border_c: Color
) -> StyleBoxFlat:
	var sb := StyleBoxFlat.new()
	sb.bg_color = bg
	sb.set_corner_radius_all(int(round(radius)))
	sb.content_margin_left = pad_h
	sb.content_margin_right = pad_h
	sb.content_margin_top = pad_v
	sb.content_margin_bottom = pad_v
	sb.border_width_left = border_w
	sb.border_width_right = border_w
	sb.border_width_top = border_w
	sb.border_width_bottom = border_w
	sb.border_color = border_c
	sb.anti_aliasing = false
	sb.draw_center = true
	return sb

static func _ui_font() -> SystemFont:
	var font := SystemFont.new()
	font.font_names = PackedStringArray(["Avenir Next", "Trebuchet MS", "Segoe UI", "sans-serif"])
	return font

static func _display_font() -> Font:
	return preload("res://assets/fonts/cormorant_semibold.tres")
