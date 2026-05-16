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

@export var already_label_text: String = "Contexte secondaire":
	set(value):
		already_label_text = value
		if is_node_ready():
			_already_label.text = value

@export var login_text: String = "Action secondaire":
	set(value):
		login_text = value
		if is_node_ready():
			_login_link.text = value

@export var reward_lines: PackedStringArray = []:
	set(value):
		reward_lines = value
		if is_node_ready():
			_refresh_rewards()

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _description_label: Label = %DescriptionLabel
@onready var _cta_button: AppButton = %CTAButton
@onready var _already_label: Label = %AlreadyLabel
@onready var _login_link: LinkButton = %LoginLink
@onready var _rewards_grid: GridContainer = %RewardsGrid

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
	_already_label.text = already_label_text
	_login_link.text = login_text
	_refresh_rewards()

func setup(data: Dictionary) -> void:
	if data.has("eyebrow_text"):
		eyebrow_text = str(data["eyebrow_text"])
	if data.has("title_text"):
		title_text = str(data["title_text"])
	if data.has("description_text"):
		description_text = str(data["description_text"])
	if data.has("cta_text"):
		cta_text = str(data["cta_text"])
	if data.has("already_label_text"):
		already_label_text = str(data["already_label_text"])
	if data.has("login_text"):
		login_text = str(data["login_text"])
	if data.has("reward_lines") and data["reward_lines"] is PackedStringArray:
		reward_lines = data["reward_lines"]
	elif data.has("reward_lines") and data["reward_lines"] is Array:
		var lines: PackedStringArray = []
		for item: Variant in data["reward_lines"]:
			lines.append(str(item))
		reward_lines = lines

func _apply_card_style() -> void:
	var panel := StyleBoxFlat.new()
	panel.bg_color = DesignTokens.Colors.PanelTokens.Dark.BG_END
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

	_title_label.theme_type_variation = "LabelDisplayHero"
	_title_label.add_theme_font_size_override("font_size", DesignTokens.Typography.Size.HERO)

	_description_label.theme_type_variation = "LabelSecondaryLg"

func _refresh_rewards() -> void:
	_rewards_grid.visible = reward_lines.size() > 0
	for child: Node in _rewards_grid.get_children():
		child.queue_free()
	for line: String in reward_lines:
		var badge := PanelContainer.new()
		badge.size_flags_horizontal = Control.SIZE_EXPAND_FILL
		var sb := StyleBoxFlat.new()
		sb.bg_color = DesignTokens.Colors.Status.NotStarted.BG
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
		label.theme_type_variation = "LabelPrimarySm"
		badge.add_child(label)
		_rewards_grid.add_child(badge)
