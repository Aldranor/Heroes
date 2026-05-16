extends PanelContainer
class_name AppOnboardingJourneyPanel

@export var eyebrow_text: String = "VOTRE PARCOURS":
	set(value):
		eyebrow_text = value
		if is_node_ready():
			_eyebrow_label.text = value

@export var title_text: String = "Un système d’entraînement progressif basé sur des missions.":
	set(value):
		title_text = value
		if is_node_ready():
			_title_label.text = value

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _rows: VBoxContainer = %Rows

const FONT_DISPLAY: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_BODY: Font = preload("res://assets/fonts/inter_medium.tres")

var _items: Array[Dictionary] = [
	{"kicker": "S’ENTRAÎNER", "title": "Modules courts", "desc": "Préparation rapide avant chaque départ."},
	{"kicker": "PARTIR EN MISSION", "title": "Défis interactifs", "desc": "Objectifs clairs et validation immédiate."},
	{"kicker": "PROGRESSER", "title": "Expérience et déblocages", "desc": "Suivi des performances et nouveaux contenus."},
]

func _ready() -> void:
	_apply_panel_style()
	_apply_text_style()
	_eyebrow_label.text = eyebrow_text
	_title_label.text = title_text
	_render_rows()

func _apply_panel_style() -> void:
	var panel := StyleBoxFlat.new()
	panel.bg_color = Color(0.13, 0.10, 0.09, 0.92)
	panel.border_color = DesignTokens.Colors.PanelTokens.Dark.BORDER
	panel.border_width_left = 1
	panel.border_width_top = 1
	panel.border_width_right = 1
	panel.border_width_bottom = 1
	panel.set_corner_radius_all(int(round(DesignTokens.Radius.PANEL)))
	panel.content_margin_left = DesignTokens.Spacing.XL2
	panel.content_margin_top = DesignTokens.Spacing.XL2
	panel.content_margin_right = DesignTokens.Spacing.XL2
	panel.content_margin_bottom = DesignTokens.Spacing.XL2
	add_theme_stylebox_override("panel", panel)

func _apply_text_style() -> void:
	_eyebrow_label.add_theme_font_override("font", FONT_BODY)
	_eyebrow_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.LABEL)
	_eyebrow_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Secondary.DARK)
	_title_label.add_theme_font_override("font", FONT_DISPLAY)
	_title_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.HEADING_LG)
	_title_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)

func _render_rows() -> void:
	for child: Node in _rows.get_children():
		child.queue_free()

	for item: Dictionary in _items:
		var box := VBoxContainer.new()
		box.add_theme_constant_override("separation", 4)

		var line := ColorRect.new()
		line.custom_minimum_size = Vector2(0, 1)
		line.color = Color(1, 1, 1, 0.12)
		box.add_child(line)

		var kicker := Label.new()
		kicker.text = str(item.get("kicker", ""))
		kicker.add_theme_font_override("font", FONT_BODY)
		kicker.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.LABEL)
		kicker.add_theme_color_override("font_color", DesignTokens.Colors.Text.Secondary.DARK)
		box.add_child(kicker)

		var title := Label.new()
		title.text = str(item.get("title", ""))
		title.add_theme_font_override("font", FONT_BODY)
		title.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_LG)
		title.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)
		box.add_child(title)

		var desc := Label.new()
		desc.text = str(item.get("desc", ""))
		desc.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
		desc.add_theme_font_override("font", FONT_BODY)
		desc.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_SM)
		desc.add_theme_color_override("font_color", DesignTokens.Colors.Text.Muted.DARK)
		box.add_child(desc)

		_rows.add_child(box)
