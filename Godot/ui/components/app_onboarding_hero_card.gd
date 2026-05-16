extends PanelContainer
class_name AppOnboardingHeroCard

signal cta_pressed()
signal login_pressed()

@export var eyebrow_text: String = "SECTION":
	set(value):
		eyebrow_text = value
		if is_node_ready():
			_eyebrow_label.text = value

@export var title_text: String = "Titre principal":
	set(value):
		title_text = value
		if is_node_ready():
			_title_label.text = value

@export_multiline var description_text: String = "":
	set(value):
		description_text = value
		if is_node_ready():
			_description_label.text = value

@export var cta_text: String = "ACTION PRINCIPALE":
	set(value):
		cta_text = value
		if is_node_ready():
			_cta_button.label_text = value

@export var login_text: String = "Action secondaire":
	set(value):
		login_text = value
		if is_node_ready():
			_login_link.text = value

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel
@onready var _cta_button: AppButton = %CTAButton
@onready var _login_link: LinkButton = %LoginLink

const FONT_DISPLAY: Font = preload("res://assets/fonts/cormorant_semibold.tres")

func _ready() -> void:
	_apply_card_style()
	_apply_text_style()
	_cta_button.variant = AppButton.Variant.PRIMARY
	_cta_button.pressed.connect(func() -> void: cta_pressed.emit())
	_login_link.pressed.connect(func() -> void: login_pressed.emit())
	_eyebrow_label.text = eyebrow_text
	_title_label.text = title_text
	_description_label.text = description_text
	_cta_button.label_text = cta_text
	_login_link.text = login_text

func setup(data: Dictionary) -> void:
	if data.has("eyebrow_text"):
		eyebrow_text = str(data["eyebrow_text"])
	if data.has("title_text"):
		title_text = str(data["title_text"])
	if data.has("description_text"):
		description_text = str(data["description_text"])
	if data.has("cta_text"):
		cta_text = str(data["cta_text"])
	if data.has("login_text"):
		login_text = str(data["login_text"])

func _apply_card_style() -> void:
	var panel := StyleBoxFlat.new()
	panel.bg_color = Color(0.05, 0.04, 0.04, 0.92)
	panel.border_color = DesignTokens.Colors.PanelTokens.Dark.BORDER
	var bw := int(DesignTokens.Borders.WIDTH_THIN)
	panel.border_width_left = bw
	panel.border_width_top = bw
	panel.border_width_right = bw
	panel.border_width_bottom = bw
	panel.set_corner_radius_all(int(round(DesignTokens.Radius.PANEL)))
	panel.content_margin_left = DesignTokens.Spacing.XL2
	panel.content_margin_top = DesignTokens.Spacing.XL2
	panel.content_margin_right = DesignTokens.Spacing.XL2
	panel.content_margin_bottom = DesignTokens.Spacing.XL2
	add_theme_stylebox_override("panel", panel)

func _apply_text_style() -> void:
	_eyebrow_label.theme_type_variation = "LabelEyebrow"
	_eyebrow_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.LABEL)

	_title_label.add_theme_font_override("font", FONT_DISPLAY)
	_title_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.HERO)
	_title_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)

	_description_label.theme_type_variation = "LabelSecondary"
	_description_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_LG)

	_login_link.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_SM)
