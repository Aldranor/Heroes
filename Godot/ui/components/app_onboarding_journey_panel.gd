extends PanelContainer
class_name AppOnboardingJourneyPanel

@export var eyebrow_text: String = "SECTION":
	set(value):
		eyebrow_text = value
		if is_node_ready():
			_eyebrow_label.text = value

@export var title_text: String = "Titre du panneau":
	set(value):
		title_text = value
		if is_node_ready():
			_title_label.text = value

@export var journey_items: Array[Dictionary] = []:
	set(value):
		journey_items = value
		if is_node_ready():
			_render_rows()

@onready var _eyebrow_label: Label = %EyebrowLabel
@onready var _title_label: Label = %TitleLabel
@onready var _rows: VBoxContainer = %Rows

func _ready() -> void:
	_apply_panel_style()
	_apply_text_style()
	_eyebrow_label.text = eyebrow_text
	_title_label.text = title_text
	_render_rows()

func setup(data: Dictionary) -> void:
	if data.has("eyebrow_text"):
		eyebrow_text = str(data["eyebrow_text"])
	if data.has("title_text"):
		title_text = str(data["title_text"])
	if data.has("journey_items") and data["journey_items"] is Array:
		var items: Array[Dictionary] = []
		for item: Variant in data["journey_items"]:
			if item is Dictionary:
				items.append(item)
		journey_items = items

func _apply_panel_style() -> void:
	var panel := StyleBoxFlat.new()
	panel.bg_color = DesignTokens.Colors.Card.Dark.BG_END
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
	_eyebrow_label.theme_type_variation = "LabelEyebrow"
	_title_label.theme_type_variation = "LabelDisplayLg"

func _render_rows() -> void:
	_rows.visible = journey_items.size() > 0
	for child: Node in _rows.get_children():
		child.queue_free()

	for item: Dictionary in journey_items:
		var box := VBoxContainer.new()
		box.add_theme_constant_override("separation", 4)

		var line := ColorRect.new()
		line.custom_minimum_size = Vector2(0, 1)
		line.color = DesignTokens.Colors.Card.Dark.BORDER
		box.add_child(line)

		var kicker := Label.new()
		kicker.text = str(item.get("kicker", ""))
		kicker.theme_type_variation = "LabelEyebrow"
		box.add_child(kicker)

		var title := Label.new()
		title.text = str(item.get("title", ""))
		title.theme_type_variation = "LabelPrimaryLg"
		box.add_child(title)

		var desc := Label.new()
		desc.text = str(item.get("desc", ""))
		desc.autowrap_mode = TextServer.AUTOWRAP_WORD_SMART
		desc.theme_type_variation = "LabelMuted"
		box.add_child(desc)

		_rows.add_child(box)
