extends Control

signal data_changed(data: Dictionary)

const MOCK = preload("res://mock/avatar_options.gd")

var selected_slug: String = ""

@onready var _card_container: HBoxContainer = %CardContainer
@onready var _detail_panel: PanelContainer = %DetailPanel
@onready var _detail_name: Label = %DetailName
@onready var _detail_category: Label = %DetailCategory
@onready var _detail_description: Label = %DetailDescription
@onready var _detail_personality: Label = %DetailPersonality
@onready var _detail_hp_bar: Control = %DetailHpBar
@onready var _detail_hp_value: Label = %DetailHpValue
@onready var _detail_attack_bar: Control = %DetailAttackBar
@onready var _detail_attack_value: Label = %DetailAttackValue
@onready var _detail_defense_bar: Control = %DetailDefenseBar
@onready var _detail_defense_value: Label = %DetailDefenseValue
@onready var _detail_speed_bar: Control = %DetailSpeedBar
@onready var _detail_speed_value: Label = %DetailSpeedValue
@onready var _title_label: Label = %TitleLabel

const MAX_HP: int = 100
const MAX_ATK: int = 30
const MAX_DEF: int = 20
const MAX_SPD: int = 30


func _ready() -> void:
	_build_cards()


func set_data(data: Dictionary) -> void:
	if data.has("selected_companion_slug") and str(data["selected_companion_slug"]) != "":
		selected_slug = str(data["selected_companion_slug"])
		_refresh_selection()
		_show_detail_for_slug(selected_slug)


func get_data() -> Dictionary:
	return {
		"selected_companion_slug": selected_slug,
	}


func _build_cards() -> void:
	var companions: Array[Dictionary] = MOCK.get_starter_companions()
	for comp: Dictionary in companions:
		var card: PanelContainer = PanelContainer.new()
		card.custom_minimum_size = Vector2(100, 180)
		card.size_flags_horizontal = Control.SIZE_EXPAND
		card.mouse_filter = Control.MOUSE_FILTER_PASS

		var vbox: VBoxContainer = VBoxContainer.new()
		vbox.mouse_filter = Control.MOUSE_FILTER_IGNORE
		card.add_child(vbox)

		var sprite_placeholder: ColorRect = ColorRect.new()
		sprite_placeholder.custom_minimum_size = Vector2(80, 64)
		sprite_placeholder.color = comp.get("color", Color("4682b4"))
		sprite_placeholder.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		sprite_placeholder.mouse_filter = Control.MOUSE_FILTER_IGNORE
		vbox.add_child(sprite_placeholder)

		var name_label: Label = Label.new()
		name_label.text = str(comp.get("name", ""))
		name_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
		name_label.autowrap_mode = TextServer.AUTOWRAP_WORD
		name_label.mouse_filter = Control.MOUSE_FILTER_IGNORE
		vbox.add_child(name_label)

		var rarity_color: Color = _rarity_to_token(comp.get("rarity", "common"))
		var rarity_label: Label = Label.new()
		rarity_label.text = str(comp.get("rarity", "")).capitalize()
		rarity_label.horizontal_alignment = HORIZONTAL_ALIGNMENT_CENTER
		rarity_label.add_theme_color_override("font_color", rarity_color)
		rarity_label.mouse_filter = Control.MOUSE_FILTER_IGNORE
		vbox.add_child(rarity_label)

		var slug: String = str(comp.get("slug", ""))
		card.gui_input.connect(_on_card_input.bind(slug, card))

		_card_container.add_child(card)

		if slug == selected_slug:
			_apply_card_highlight(card, true)


func _on_card_input(event: InputEvent, slug: String, card: PanelContainer) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		_select_companion(slug, card)
	if event is InputEventScreenTouch and event.pressed:
		_select_companion(slug, card)


func _select_companion(slug: String, card: PanelContainer) -> void:
	selected_slug = slug
	AudioManager.play_sfx("click", -5.0)
	_refresh_selection()
	_apply_card_highlight(card, true)
	_show_detail_for_slug(slug)
	_animate_card(card)
	emit_changed()


func _refresh_selection() -> void:
	for child: Node in _card_container.get_children():
		if child is PanelContainer:
			var card: PanelContainer = child
			var is_selected: bool = false
			for c: Node in card.get_children():
				if c is VBoxContainer:
					for c2: Node in c.get_children():
						if c2 is Label and c2.get_index() == 1:
							var name_text: String = c2.text
							for comp: Dictionary in MOCK.get_starter_companions():
								if str(comp.get("name", "")) == name_text and str(comp.get("slug", "")) == selected_slug:
									is_selected = true
									break
			if not is_selected:
				_apply_card_highlight(card, false)


func _apply_card_highlight(card: PanelContainer, selected: bool) -> void:
	var style: StyleBoxFlat = StyleBoxFlat.new()
	style.anti_aliasing = false
	style.set_corner_radius_all(int(round(DesignTokens.Radius.CARD_SM)))
	style.content_margin_left = DesignTokens.Spacing.SM
	style.content_margin_top = DesignTokens.Spacing.SM
	style.content_margin_right = DesignTokens.Spacing.SM
	style.content_margin_bottom = DesignTokens.Spacing.SM
	var bw := int(DesignTokens.Borders.WIDTH_THIN)
	if selected:
		style.bg_color = DesignTokens.Colors.Card.Dark.BG_START
		style.border_color = DesignTokens.Colors.Card.Dark.BORDER_SELECTED
		style.border_width_left = bw
		style.border_width_top = bw
		style.border_width_right = bw
		style.border_width_bottom = bw
	else:
		style.bg_color = DesignTokens.Colors.Card.Dark.BG_START * Color(1, 1, 1, 0.6)
		style.border_color = DesignTokens.Colors.Card.Dark.BORDER
		style.border_width_left = bw
		style.border_width_top = bw
		style.border_width_right = bw
		style.border_width_bottom = bw
	card.add_theme_stylebox_override("panel", style)


func _show_detail_for_slug(slug: String) -> void:
	for comp: Dictionary in MOCK.get_starter_companions():
		if str(comp.get("slug", "")) == slug:
			_detail_name.text = str(comp.get("name", ""))
			_detail_category.text = str(comp.get("category", ""))
			_detail_description.text = str(comp.get("description", ""))
			_detail_personality.text = tr("Personnalité : %s") % str(comp.get("personality", ""))

			var stats: Dictionary = comp.get("stats", {})
			var hp: int = int(stats.get("hp", 0))
			var atk: int = int(stats.get("attack", 0))
			var defense: int = int(stats.get("defense", 0))
			var spd: int = int(stats.get("speed", 0))

			_update_stat_bar(_detail_hp_bar, _detail_hp_value, hp, MAX_HP, DesignTokens.Colors.Specialization.DANGERS)
			_update_stat_bar(_detail_attack_bar, _detail_attack_value, atk, MAX_ATK, DesignTokens.Colors.Accent.PRIMARY)
			_update_stat_bar(_detail_defense_bar, _detail_defense_value, defense, MAX_DEF, DesignTokens.Colors.Specialization.SPEED)
			_update_stat_bar(_detail_speed_bar, _detail_speed_value, spd, MAX_SPD, DesignTokens.Colors.Specialization.SAFETY)
			break


func _update_stat_bar(bar: Control, label: Label, value: int, max_val: int, fill_color: Color) -> void:
	label.text = "%d / %d" % [value, max_val]
	if bar.has_method("set_values"):
		bar.set_values(value, max_val)
	var progress: ProgressBar = bar.get_node_or_null("ProgressBar")
	if progress != null:
		progress.max_value = max_val
		progress.value = value
		var fill: StyleBoxFlat = StyleBoxFlat.new()
		fill.bg_color = fill_color
		fill.anti_aliasing = false
		fill.set_corner_radius_all(int(round(DesignTokens.Radius.PILL)))
		progress.add_theme_stylebox_override("fill", fill)


func _animate_card(card: PanelContainer) -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_BOUNCE)
	card.scale = Vector2(1.05, 1.05)
	tween.tween_property(card, "scale", Vector2(1, 1), 0.3)


func emit_changed() -> void:
	data_changed.emit(get_data())


func _rarity_to_token(rarity: Variant) -> Color:
	match str(rarity):
		"legendary":
			return DesignTokens.Colors.Accent.PRIMARY
		"rare":
			return DesignTokens.Colors.Specialization.SIGNS
		"uncommon":
			return DesignTokens.Colors.Specialization.SAFETY
		_:
			return DesignTokens.Colors.Text.Muted.DARK
