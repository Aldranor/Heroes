extends Control

signal tutorial_complete()

const SLIDES: Array[Dictionary] = [
	{
		"title": "Étudie pour gagner en puissance",
		"description": "Réponds aux questions sur le code de la route pour gagner de l'expérience et renforcer ton équipe.",
		"icon_color": DesignTokens.Colors.Accent.PRIMARY,
		"icon_symbol": "📖"
	},
	{
		"title": "Combats les esprits de la route",
		"description": "Utilise tes connaissances pour vaincre les esprits dans des combats stratégiques au tour par tour.",
		"icon_color": DesignTokens.Colors.Specialization.DANGERS,
		"icon_symbol": "⚔"
	},
	{
		"title": "Progresse sur la carte d'aventure",
		"description": "Débloque de nouvelles zones, découvre des défis et deviens le maître du code de la route.",
		"icon_color": DesignTokens.Colors.Specialization.SAFETY,
		"icon_symbol": "🗺"
	},
]

var _current_slide: int = 0
var _drag_start_x: float = 0.0
var _is_dragging: bool = false

@onready var _slide_container: Control = %SlideContainer
@onready var _slide_title: Label = %SlideTitle
@onready var _slide_description: Label = %SlideDescription
@onready var _slide_icon: ColorRect = %SlideIcon
@onready var _icon_label: Label = %IconLabel
@onready var _page_indicator: HBoxContainer = %PageIndicator
@onready var _action_button: Button = %ActionButton
@onready var _prev_button: Button = %PrevButton

func _ready() -> void:
	_build_page_indicator()
	_action_button.pressed.connect(_on_action_pressed)
	_prev_button.pressed.connect(_on_prev_pressed)
	_show_slide(0)

func set_data(_data: Dictionary) -> void:
	pass

func get_data() -> Dictionary:
	return {}

func _build_page_indicator() -> void:
	for i: int in range(SLIDES.size()):
		var dot: ColorRect = ColorRect.new()
		dot.custom_minimum_size = Vector2(8, 8)
		dot.color = DesignTokens.Colors.PanelTokens.Dark.BORDER
		dot.size_flags_horizontal = Control.SIZE_SHRINK_CENTER
		_page_indicator.add_child(dot)

func _show_slide(index: int) -> void:
	_current_slide = clampi(index, 0, SLIDES.size() - 1)
	var slide: Dictionary = SLIDES[_current_slide]

	_slide_title.text = str(slide.get("title", ""))
	_slide_description.text = str(slide.get("description", ""))
	_slide_icon.color = slide.get("icon_color", Color.WHITE)
	_icon_label.text = str(slide.get("icon_symbol", "?"))

	var is_last: bool = _current_slide >= SLIDES.size() - 1
	_action_button.text = "Commencer l'aventure !" if is_last else "Suivant"
	_prev_button.visible = _current_slide > 0

	_update_page_indicator()
	_play_slide_animation()

func _update_page_indicator() -> void:
	var dots: Array[Node] = _page_indicator.get_children()
	for i: int in range(dots.size()):
		if dots[i] is ColorRect:
			var dot: ColorRect = dots[i]
			dot.color = DesignTokens.Colors.Accent.PRIMARY if i == _current_slide else DesignTokens.Colors.PanelTokens.Dark.BORDER

func _play_slide_animation() -> void:
	var tween: Tween = create_tween().set_ease(Tween.EASE_OUT).set_trans(Tween.TRANS_QUART)
	_slide_container.modulate = Color(1, 1, 1, 0)
	_slide_container.position = Vector2(30, 0)
	tween.tween_property(_slide_container, "modulate", Color(1, 1, 1, 1), 0.3)
	tween.parallel().tween_property(_slide_container, "position", Vector2(0, 0), 0.3)

func _on_action_pressed() -> void:
	AudioManager.play_sfx("click", -5.0)
	if _current_slide >= SLIDES.size() - 1:
		tutorial_complete.emit()
	else:
		_show_slide(_current_slide + 1)

func _on_prev_pressed() -> void:
	AudioManager.play_sfx("click", -5.0)
	_show_slide(_current_slide - 1)

func _gui_input(event: InputEvent) -> void:
	if event is InputEventScreenTouch:
		if event.pressed:
			_drag_start_x = event.position.x
			_is_dragging = true
		else:
			if _is_dragging:
				var delta: float = event.position.x - _drag_start_x
				if delta < -50 and _current_slide < SLIDES.size() - 1:
					_show_slide(_current_slide + 1)
				elif delta > 50 and _current_slide > 0:
					_show_slide(_current_slide - 1)
				_is_dragging = false
