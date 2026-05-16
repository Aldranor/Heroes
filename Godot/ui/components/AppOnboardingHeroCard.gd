extends PanelContainer
class_name AppOnboardingHeroCard

signal cta_pressed()
signal login_pressed()

@export var eyebrow_text: String = "ADMISSION":
	set(value):
		eyebrow_text = value
		if is_node_ready():
			_eyebrow_label.text = value

@export var title_text: String = "Académie des Héros":
	set(value):
		title_text = value
		if is_node_ready():
			_title_label.text = value

@export_multiline var description_text: String = "":
	set(value):
		description_text = value
		if is_node_ready():
			_description_label.text = value

@export var cta_text: String = "REJOINDRE L'ACADÉMIE":
	set(value):
		cta_text = value
		if is_node_ready():
			_cta_button.label_text = value

@export var login_text: String = "Se connecter":
	set(value):
		login_text = value
		if is_node_ready():
			_login_link.text = value

@export var reward_lines: PackedStringArray = ["+120 XP", "MISSION RÉUSSIE", "NOUVELLE MISSION DÉBLOQUÉE", "NIVEAU SUPÉRIEUR ATTEINT"]:
	set(value):
		reward_lines = value
		if is_node_ready():
			_refresh_rewards()

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel
@onready var _cta_button: AppButton = %CTAButton
@onready var _login_link: LinkButton = %LoginLink
@onready var _rewards_grid: GridContainer = %RewardsGrid

const FONT_DISPLAY: Font = preload("res://assets/fonts/cormorant_semibold.tres")
const FONT_BODY: Font = preload("res://assets/fonts/inter_medium.tres")

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
	_refresh_rewards()

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
	_eyebrow_label.add_theme_font_override("font", FONT_BODY)
	_eyebrow_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.LABEL)
	_eyebrow_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Secondary.DARK)

	_title_label.add_theme_font_override("font", FONT_DISPLAY)
	_title_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.HERO)
	_title_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)

	_description_label.add_theme_font_override("font", FONT_BODY)
	_description_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_LG)
	_description_label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Secondary.DARK)

	_login_link.add_theme_font_override("font", FONT_BODY)
	_login_link.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_SM)

func _refresh_rewards() -> void:
	for child: Node in _rewards_grid.get_children():
		child.queue_free()
	for line: String in reward_lines:
		var badge := PanelContainer.new()
		badge.size_flags_horizontal = Control.SIZE_EXPAND_FILL
		var sb := StyleBoxFlat.new()
		sb.bg_color = Color(0, 0, 0, 0.25)
		sb.border_color = DesignTokens.Colors.PanelTokens.Dark.BORDER
		sb.border_width_left = 1
		sb.border_width_top = 1
		sb.border_width_right = 1
		sb.border_width_bottom = 1
		sb.set_corner_radius_all(int(round(DesignTokens.Radius.CARD_SM)))
		sb.content_margin_left = DesignTokens.Spacing.LG
		sb.content_margin_right = DesignTokens.Spacing.LG
		sb.content_margin_top = DesignTokens.Spacing.SM
		sb.content_margin_bottom = DesignTokens.Spacing.SM
		badge.add_theme_stylebox_override("panel", sb)
		var label := Label.new()
		label.text = line
		label.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
		label.add_theme_font_override("font", FONT_BODY)
		label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.BODY_SM)
		label.add_theme_color_override("font_color", DesignTokens.Colors.Text.Primary.DARK)
		badge.add_child(label)
		_rewards_grid.add_child(badge)
