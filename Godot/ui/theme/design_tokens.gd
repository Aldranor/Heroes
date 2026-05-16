extends RefCounted
class_name DesignTokens

## Centralized design tokens for Godot 4 UI.
## Mirrors shared/design-system/design-tokens.json — the single source of truth.
##
## Usage:
##   var bg := DesignTokens.Colors.Shell.Dark.BG_START
##   var pad := DesignTokens.Spacing.LG
##   var font := DesignTokens.Typography.Size.BODY
##
## Note: inner class names use *Tokens suffix (PanelTokens, InputTokens, etc.)
## to avoid hiding native Godot classes (Panel, Input, Button, ProgressBar).


# ============================================================
# Colors
# ============================================================
class Colors:
	class Shell:
		class Dark:
			const BG_START := Color("1a1715")
			const BG_END := Color("100f0e")
			const GRID := Color(1.0, 1.0, 1.0, 0.03)

		class Light:
			const BG_START := Color("f8f2e9")
			const BG_MID := Color("f2ebdf")
			const BG_END := Color("eee4d6")

	class PanelTokens:
		class Dark:
			const BG_START := Color(37.0 / 255.0, 31.0 / 255.0, 28.0 / 255.0, 0.94)
			const BG_END := Color(20.0 / 255.0, 18.0 / 255.0, 17.0 / 255.0, 0.98)
			const BORDER := Color(201.0 / 255.0, 168.0 / 255.0, 122.0 / 255.0, 0.24)
			const INNER_BORDER := Color(1.0, 1.0, 1.0, 0.04)
			const INSET_HIGHLIGHT := Color(1.0, 1.0, 1.0, 0.05)

			# Shadow: 0 22px 50px rgba(0,0,0,0.28)
			const SHADOW := {
				x = 0,
				y = 22,
				blur = 50,
				spread = 0,
				color = Color(0.0, 0.0, 0.0, 0.28),
				inset = false,
			}

		class Light:
			const BG := Color("ffffff")
			const BORDER := Color("e7e5e4")

			# Shadow: 0 20px 40px rgba(15,23,42,0.08)
			const SHADOW := {
				x = 0,
				y = 20,
				blur = 40,
				spread = 0,
				color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.08),
				inset = false,
			}

	class Card:
		class Dark:
			const BG_START := Color(52.0 / 255.0, 45.0 / 255.0, 40.0 / 255.0, 0.92)
			const BG_END := Color(29.0 / 255.0, 26.0 / 255.0, 24.0 / 255.0, 0.96)
			const BORDER := Color(214.0 / 255.0, 197.0 / 255.0, 168.0 / 255.0, 0.14)
			const BORDER_HOVER := Color(238.0 / 255.0, 203.0 / 255.0, 128.0 / 255.0, 0.32)
			const BORDER_SELECTED := Color(245.0 / 255.0, 198.0 / 255.0, 100.0 / 255.0, 0.9)

			# Shadow: 0 18px 28px rgba(0,0,0,0.16)
			const SHADOW := {
				x = 0,
				y = 18,
				blur = 28,
				spread = 0,
				color = Color(0.0, 0.0, 0.0, 0.16),
				inset = false,
			}

		class Light:
			const BG := Color(1.0, 1.0, 1.0, 0.84)
			const BORDER := Color(214.0 / 255.0, 211.0 / 255.0, 209.0 / 255.0, 0.95)
			const BORDER_HOVER := Color(28.0 / 255.0, 25.0 / 255.0, 23.0 / 255.0, 0.84)

			# Shadow: 0 18px 34px rgba(15,23,42,0.1)
			const SHADOW_HOVER := {
				x = 0,
				y = 18,
				blur = 34,
				spread = 0,
				color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.1),
				inset = false,
			}

			const BG_SELECTED := Color("ffffff")

	class Hero:
		const ORANGE_ACCENT := Color(249.0 / 255.0, 115.0 / 255.0, 22.0 / 255.0, 0.24)
		const BLUE_ACCENT := Color(59.0 / 255.0, 130.0 / 255.0, 246.0 / 255.0, 0.16)
		const BG_START := Color(55.0 / 255.0, 43.0 / 255.0, 36.0 / 255.0, 0.98)
		const BG_END := Color(26.0 / 255.0, 23.0 / 255.0, 21.0 / 255.0, 0.98)

	class Text:
		class Primary:
			const DARK := Color("f5efe7")
			const LIGHT := Color("1c1917")

		class Secondary:
			const DARK := Color("bfa98f")
			const LIGHT := Color("57534e")

		class Muted:
			const DARK := Color("a8a29e")
			const LIGHT := Color("a8a29e")

		class Disabled:
			const DARK := Color("78716c")
			const LIGHT := Color("d6d3d1")

	class Accent:
		const PRIMARY := Color("fde68a")
		const PRIMARY_HOVER := Color("fef08a")
		const PRIMARY_TEXT := Color("0c0a09")
		const CLASS_STRATEGIST := Color("5B6CFF")
		const CLASS_OBSERVER := Color("28A66A")
		const CLASS_SCOUT := Color("FF8A3D")

	class Districts:
		const AMBER := Color("f59e0b")
		const TEAL := Color("14b8a6")
		const SKY := Color("0ea5e9")
		const VIOLET := Color("8b5cf6")
		const EMERALD := Color("10b981")
		const ROSE := Color("f43f5e")
		const STONE := Color("78716c")

	class Specialization:
		const PRIORITY := Color("D97745")
		const SIGNS := Color("F2C14E")
		const SPEED := Color("52B3D9")
		const SAFETY := Color("65A30D")
		const DANGERS := Color("C2410C")
		const MIXED := Color("A855F7")

	class Status:
		class NotStarted:
			const BORDER := Color(1.0, 1.0, 1.0, 0.1)
			const BG := Color(1.0, 1.0, 1.0, 0.05)
			const TEXT := Color("d6d3d1")

		class InProgress:
			const BORDER := Color(253.0 / 255.0, 230.0 / 255.0, 138.0 / 255.0, 0.25)
			const BG := Color(252.0 / 255.0, 211.0 / 255.0, 77.0 / 255.0, 0.12)
			const TEXT := Color("fefce8")

		class Mastered:
			const BORDER := Color(167.0 / 255.0, 243.0 / 255.0, 208.0 / 255.0, 0.25)
			const BG := Color(110.0 / 255.0, 231.0 / 255.0, 183.0 / 255.0, 0.14)
			const TEXT := Color("ecfdf5")

		class Locked:
			const BORDER := Color(253.0 / 255.0, 164.0 / 255.0, 175.0 / 255.0, 0.25)
			const BG := Color(251.0 / 255.0, 113.0 / 255.0, 133.0 / 255.0, 0.12)
			const TEXT := Color("fff1f2")

	class Badge:
		class Dark:
			const BG := Color(1.0, 247.0 / 255.0, 237.0 / 255.0, 0.08)
			const TEXT := Color("eadac2")
			const HIGHLIGHT := Color(1.0, 1.0, 1.0, 0.08)

	class Divider:
		class Dark:
			# CSS: linear-gradient(90deg, transparent 0%, rgba(218,184,128,0.26) 20%, rgba(218,184,128,0.08) 80%, transparent 100%)
			const COLOR_PEAK := Color(218.0 / 255.0, 184.0 / 255.0, 128.0 / 255.0, 0.26)
			const COLOR_TAIL := Color(218.0 / 255.0, 184.0 / 255.0, 128.0 / 255.0, 0.08)
			const PEAK_POSITION := 0.2
			const TAIL_POSITION := 0.8

	class ProgressBarTokens:
		const TRACK_BG := Color(1.0, 1.0, 1.0, 0.08)
		const FILL_BG := Color("fde68a")

	class ZincScale:
		const Z50 := Color("fafafa")
		const Z100 := Color("f5f5f5")
		const Z200 := Color("e5e5e5")
		const Z300 := Color("d4d4d4")
		const Z400 := Color("a3a3a3")
		const Z500 := Color("737373")
		const Z600 := Color("525252")
		const Z700 := Color("404040")
		const Z800 := Color("262626")
		const Z900 := Color("171717")
		const Z950 := Color("0a0a0a")

	class AvatarDefaults:
		const SKIN := Color("F7D7C4")
		const HAIR := Color("1E1E1E")

	class InputTokens:
		class Dark:
			const BG := Color(1.0, 1.0, 1.0, 0.04)
			const BORDER := Color(1.0, 1.0, 1.0, 0.06)
			const FOCUS_RING := Color(1.0, 1.0, 1.0, 0.06)

		class Light:
			const BG := Color("ffffff")
			const BORDER := Color("e7e5e4")
			const PLACEHOLDER := Color("a8a29e")
			const FOCUS_BORDER := Color("a8a29e")
			const FOCUS_RING := Color(214.0 / 255.0, 211.0 / 255.0, 209.0 / 255.0, 0.7)

	class Sidebar:
		class Dark:
			const BG := Color(23.0 / 255.0, 20.0 / 255.0, 18.0 / 255.0, 0.95)
			const BORDER := Color(245.0 / 255.0, 158.0 / 255.0, 11.0 / 255.0, 0.1)
			const TEXT := Color("f5f5f4")

	class Navigation:
		class Dark:
			const ITEM_HOVER_BG := Color(1.0, 1.0, 1.0, 0.06)
			const ITEM_ACTIVE_BG := Color(1.0, 1.0, 1.0, 0.08)
			const ITEM_ACTIVE_BORDER := Color("fde68a")


# ============================================================
# Spacing
# ============================================================
class Spacing:
	const XS := 4
	const SM := 8
	const MD := 12
	const LG := 16
	const XL := 20
	const XL2 := 24
	const XL3 := 28
	const SECTION_GAP := 24


# ============================================================
# Radius
# ============================================================
class Radius:
	const NONE := 0.0
	const SM := 9.6
	const PILL := 14.4
	const CHIP := 16.0
	const FORM := 20.0
	const CARD_SM := 22.4
	const CARD := 25.6
	const CARD_LG := 27.2
	const CARD_XL := 28.0
	const PANEL := 32.0
	const FULL := 9999.0


# ============================================================
# Typography
# ============================================================
class Typography:
	const FONT_SANS := ["Avenir Next", "Trebuchet MS", "Segoe UI", "sans-serif"]
	const FONT_DISPLAY := ["Iowan Old Style", "Palatino Linotype", "Book Antiqua", "Georgia", "serif"]

	class Size:
		const LABEL := 11
		const BADGE := 11
		const CAPTION := 10
		const BODY_SM := 14
		const BODY := 16
		const BODY_LG := 18
		const HEADING_SM := 20
		const HEADING_MD := 24
		const HEADING_LG := 30
		const HEADING_XL := 36
		const HERO := 48

	class Weight:
		const REGULAR := 400
		const SEMIBOLD := 600
		const BOLD := 700

	class LetterSpacing:
		const LABEL := 0.28
		const BADGE := 0.22
		const STATUS := 0.16
		const CTA := 0.16
		const DISPLAY := -0.03

	class LineHeight:
		const TIGHT := 1.25
		const NORMAL := 1.5
		const RELAXED := 1.75


# ============================================================
# Borders
# ============================================================
class Borders:
	const WIDTH_THIN := 1.0
	const STYLE_SOLID := "solid"


# ============================================================
# Shadows
# ============================================================
class Shadows:
	# Panel dark: 0 22px 50px rgba(0,0,0,0.28) + inset 0 1px 0 rgba(255,255,255,0.05)
	const PANEL_DARK := {
		x = 0,
		y = 22,
		blur = 50,
		spread = 0,
		color = Color(0.0, 0.0, 0.0, 0.28),
		inset = false,
	}
	const PANEL_DARK_INSET_HIGHLIGHT := {
		x = 0,
		y = 1,
		blur = 0,
		spread = 0,
		color = Color(1.0, 1.0, 1.0, 0.05),
		inset = true,
	}

	# Card dark: 0 18px 28px rgba(0,0,0,0.16)
	const CARD_DARK := {
		x = 0,
		y = 18,
		blur = 28,
		spread = 0,
		color = Color(0.0, 0.0, 0.0, 0.16),
		inset = false,
	}

	# Card light: 0 20px 40px rgba(15,23,42,0.08)
	const CARD_LIGHT := {
		x = 0,
		y = 20,
		blur = 40,
		spread = 0,
		color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.08),
		inset = false,
	}

	# Card light hover: 0 18px 34px rgba(15,23,42,0.1)
	const CARD_LIGHT_HOVER := {
		x = 0,
		y = 18,
		blur = 34,
		spread = 0,
		color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.1),
		inset = false,
	}

	# Primary button light: 0 18px 32px rgba(15,23,42,0.16)
	const PRIMARY_BUTTON_LIGHT := {
		x = 0,
		y = 18,
		blur = 32,
		spread = 0,
		color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.16),
		inset = false,
	}

	# Preview dark: 0 24px 48px rgba(0,0,0,0.22)
	const PREVIEW_DARK := {
		x = 0,
		y = 24,
		blur = 48,
		spread = 0,
		color = Color(0.0, 0.0, 0.0, 0.22),
		inset = false,
	}

	# Slider button: 0 10px 20px rgba(15,23,42,0.08)
	const SLIDER_BUTTON := {
		x = 0,
		y = 10,
		blur = 20,
		spread = 0,
		color = Color(15.0 / 255.0, 23.0 / 255.0, 42.0 / 255.0, 0.08),
		inset = false,
	}

	# Selected ring: 0 0 0 2px rgba(245,198,100,0.2)
	const SELECTED_RING := {
		x = 0,
		y = 0,
		blur = 0,
		spread = 2,
		color = Color(245.0 / 255.0, 198.0 / 255.0, 100.0 / 255.0, 0.2),
		inset = false,
	}


# ============================================================
# Component Sizes
# ============================================================
class ComponentSizes:
	class ButtonTokens:
		const SM_H := 34.0
		const SM_PAD_H := 12.0
		const SM_PAD_V := 6.0

		const MD_H := 38.0
		const MD_PAD_H := 16.0
		const MD_PAD_V := 8.0

		const LG_H := 44.0
		const LG_PAD_H := 24.0
		const LG_PAD_V := 12.0

		const XL_H := 52.0
		const XL_PAD_H := 20.0
		const XL_PAD_V := 16.0

	class InputTokens:
		const HEIGHT := 48.0
		const TEXTAREA_HEIGHT := 120.0

	class Badge:
		const HEIGHT := 22.0
		const PAD_H := 12.0
		const PAD_V := 4.0

	class StatChip:
		const PAD_H := 16.0
		const PAD_V := 12.0

	class MapCard:
		const IMAGE_HEIGHT := 128.0

	class AvatarPreview:
		const STAGE_HEIGHT := 288.0
		const STAGE_WIDTH := 332.0
		const HUB_PREVIEW_WIDTH := 288.0
		const HUB_PREVIEW_SCALE := 1.22
		const ONBOARDING_PREVIEW_SCALE := 1.34

	class CompanionSprite:
		const SM := 32.0
		const MD := 48.0

	class ProgressBarTokens:
		const HEIGHT := 8.0


# ============================================================
# Themes
# ============================================================
class Themes:
	class Dark:
		const NAME := "rune-seeker"
		const PREVIEW := "rs-shell"
		const PANEL := "rs-panel"
		const HERO := "rs-hero"
		const CARD := "rs-map-card"
		const COMPANION_CARD := "rs-companion-card"
		const SIDEBAR_CARD := "rs-sidebar-card"
		const BADGE := "rs-badge"
		const LABEL := "rs-label"
		const STAT_CHIP := "rs-stat-chip"
		const DIVIDER := "rs-divider"
		const CHOICE := "rs-choice"
		const SWATCH := "rs-swatch"
		const AVATAR_CREST := "rs-avatar-crest"

	class Light:
		const NAME := "onboarding"
		const PREVIEW := "ob-shell"
		const STEP := "ob-step-card"
		const STEP_INDICATOR := "ob-step-indicator"
		const STEP_DOT := "ob-step-dot"
		const STYLE_CARD := "ob-style-card"
		const VARIANT_CARD := "ob-variant-card"
		const CLASS_CARD := "ob-class-card"
		const COMPANION_CHIP := "ob-companion-chip"
		const SLIDER_BUTTON := "ob-slider__button"
		const CHOICE_INDICATOR := "ob-choice-indicator"
		const AVATAR_FRAME := "ob-avatar-frame"
		const AVATAR_STAGE := "ob-avatar-stage"
		const PREVIEW_CARD := "ob-preview-card"
		const KICKER := "ob-kicker"


# ============================================================
# Animations
# ============================================================
class Animations:
	class Duration:
		const FAST := 0.16
		const NORMAL := 0.2
		const MEDIUM := 0.5
		const SLOW := 4.5

	class Easing:
		const DEFAULT := "ease-out"
		const EASE_IN_OUT := "ease-in-out"


# ============================================================
# Theme resolution helpers
# ============================================================

## Returns a dictionary of colors for the given theme name.
## theme_name: "dark" (rune-seeker) or "light" (onboarding).
static func resolve_colors(theme_name: String) -> Dictionary:
	var is_dark := theme_name == "dark"
	return {
		surface_bg_start = Colors.Shell.Dark.BG_START if is_dark else Colors.Shell.Light.BG_START,
		surface_bg_end = Colors.Shell.Dark.BG_END if is_dark else Colors.Shell.Light.BG_END,
		panel_bg_start = Colors.PanelTokens.Dark.BG_START if is_dark else Colors.PanelTokens.Light.BG,
		panel_bg_end = Colors.PanelTokens.Dark.BG_END if is_dark else Colors.PanelTokens.Light.BG,
		panel_border = Colors.PanelTokens.Dark.BORDER if is_dark else Colors.PanelTokens.Light.BORDER,
		card_bg_start = Colors.Card.Dark.BG_START if is_dark else Colors.Card.Light.BG,
		card_bg_end = Colors.Card.Dark.BG_END if is_dark else Colors.Card.Light.BG,
		card_border = Colors.Card.Dark.BORDER if is_dark else Colors.Card.Light.BORDER,
		card_border_hover = Colors.Card.Dark.BORDER_HOVER if is_dark else Colors.Card.Light.BORDER_HOVER,
		text_primary = Colors.Text.Primary.DARK if is_dark else Colors.Text.Primary.LIGHT,
		text_secondary = Colors.Text.Secondary.DARK if is_dark else Colors.Text.Secondary.LIGHT,
		text_muted = Colors.Text.Muted.DARK if is_dark else Colors.Text.Muted.LIGHT,
		text_disabled = Colors.Text.Disabled.DARK if is_dark else Colors.Text.Disabled.LIGHT,
		input_bg = Colors.InputTokens.Dark.BG if is_dark else Colors.InputTokens.Light.BG,
		input_border = Colors.InputTokens.Dark.BORDER if is_dark else Colors.InputTokens.Light.BORDER,
		input_focus_ring = Colors.InputTokens.Dark.FOCUS_RING if is_dark else Colors.InputTokens.Light.FOCUS_RING,
	}

## Returns the panel shadow dictionary for the given theme.
static func resolve_panel_shadow(theme_name: String) -> Dictionary:
	if theme_name == "dark":
		return Shadows.PANEL_DARK
	return Shadows.CARD_LIGHT

## Returns the card shadow dictionary for the given theme.
static func resolve_card_shadow(theme_name: String) -> Dictionary:
	if theme_name == "dark":
		return Shadows.CARD_DARK
	return Shadows.CARD_LIGHT
