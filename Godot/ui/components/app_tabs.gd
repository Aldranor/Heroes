extends PanelContainer
class_name AppTabs

signal tab_changed(index: int)

@export var tab_titles: Array[String] = []:
	set(v):
		tab_titles = v
		if is_node_ready(): _rebuild_tabs()

@export var active_index: int = 0:
	set(v):
		active_index = clampi(v, 0, max(0, tab_titles.size() - 1))
		if is_node_ready(): _refresh()

@onready var _tabs_container: HBoxContainer = %TabsContainer

var _buttons: Array[Button] = []

func _ready() -> void:
	_rebuild_tabs()

func _rebuild_tabs() -> void:
	for b in _buttons:
		b.queue_free()
	_buttons.clear()
	for i in tab_titles.size():
		var btn := Button.new()
		btn.text = tab_titles[i]
		btn.size_flags_horizontal = Control.SIZE_EXPAND_FILL
		btn.theme_type_variation = "ButtonSecondary" if i != active_index else ""
		btn.pressed.connect(_on_tab_pressed.bind(i))
		var s := StyleBoxFlat.new()
		s.content_margin_left = 12; s.content_margin_right = 12
		s.content_margin_top = 8; s.content_margin_bottom = 8
		btn.add_theme_constant_override("h_separation", 4)
		_tabs_container.add_child(btn)
		_buttons.append(btn)
	_refresh()

func _on_tab_pressed(index: int) -> void:
	if index == active_index:
		return
	active_index = index
	tab_changed.emit(index)

func _refresh() -> void:
	for i in _buttons.size():
		_buttons[i].theme_type_variation = "" if i == active_index else "ButtonSecondary"

func set_active(index: int) -> void:
	active_index = index

func set_tabs(titles: Array[String]) -> void:
	tab_titles = titles
