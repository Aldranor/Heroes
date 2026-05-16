extends PanelContainer
class_name AppBottomNav

signal nav_selected(route_name: String)

@export var tab_labels: Array[String] = ["Hub", "Cartes", "Combat", "Sac"]

@export var tab_routes: Array[String] = ["hub", "maps", "combat", "inventory"]

@export var active_route: String = "hub":
	set(value):
		active_route = value
		if is_node_ready():
			_refresh_tabs()

@export var safe_area_bottom: float = 12.0:
	set(value):
		safe_area_bottom = value
		if is_node_ready():
			_update_safe_area()

@onready var _tabs_container: HBoxContainer = %TabsContainer
@onready var _tabs: Array[PanelContainer] = [
	%Tab0,
	%Tab1,
	%Tab2,
	%Tab3,
]

@onready var _indicators: Array[PanelContainer] = [
	%Indicator0,
	%Indicator1,
	%Indicator2,
	%Indicator3,
]

@onready var _labels: Array[Label] = [
	%Label0,
	%Label1,
	%Label2,
	%Label3,
]

const FONT: Font = preload("res://assets/fonts/inter_medium.tres")

func _ready() -> void:
	custom_minimum_size = Vector2(0, 64)
	_style_panel()
	_update_safe_area()
	for i in range(_tabs.size()):
		var tab: PanelContainer = _tabs[i]
		tab.add_theme_stylebox_override("panel", StyleBoxEmpty.new())
		tab.gui_input.connect(_on_tab_gui_input.bind(i))
		tab.mouse_entered.connect(_on_tab_mouse_entered.bind(i))
		tab.mouse_exited.connect(_on_tab_mouse_exited.bind(i))
		if i < tab_labels.size():
			_labels[i].text = tab_labels[i]
	_refresh_tabs()

func _style_panel() -> void:
	var style := StyleBoxFlat.new()
	style.bg_color = Color(23.0 / 255.0, 20.0 / 255.0, 18.0 / 255.0, 0.98)
	style.border_color = Color(245.0 / 255.0, 158.0 / 255.0, 11.0 / 255.0, 0.1)
	style.border_width_top = 1
	style.corner_radius_top_left = 16.0
	style.corner_radius_top_right = 16.0
	style.content_margin_top = 6
	style.content_margin_bottom = 6
	style.content_margin_left = 4
	style.content_margin_right = 4
	add_theme_stylebox_override("panel", style)

func _update_safe_area() -> void:
	custom_minimum_size = Vector2(0, 60.0 + safe_area_bottom)
	queue_sort()

func _on_tab_gui_input(event: InputEvent, index: int) -> void:
	if event is InputEventMouseButton and event.pressed and event.button_index == MOUSE_BUTTON_LEFT:
		_activate_tab(index)
	if event is InputEventScreenTouch and event.pressed:
		_activate_tab(index)

func _on_tab_mouse_entered(index: int) -> void:
	if index >= _indicators.size():
		return
	if tab_routes[index] == active_route:
		return
	var indicator: PanelContainer = _indicators[index]
	var style := StyleBoxFlat.new()
	style.bg_color = Color(1.0, 1.0, 1.0, 0.06)
	style.corner_radius_top_left = 8.0
	style.corner_radius_top_right = 8.0
	style.corner_radius_bottom_left = 8.0
	style.corner_radius_bottom_right = 8.0
	indicator.add_theme_stylebox_override("panel", style)

func _on_tab_mouse_exited(index: int) -> void:
	if index >= _indicators.size():
		return
	_apply_indicator_state(index, tab_routes[index] == active_route)

func _activate_tab(index: int) -> void:
	if index >= tab_routes.size():
		return
	var route: String = tab_routes[index]
	if route == active_route:
		return
	active_route = route
	emit_signal("nav_selected", route)

func _refresh_tabs() -> void:
	for i in range(_tabs.size()):
		var is_active: bool = i < tab_routes.size() and tab_routes[i] == active_route
		_apply_tab_text_state(i, is_active)
		_apply_indicator_state(i, is_active)

func _apply_tab_text_state(index: int, is_active: bool) -> void:
	if index >= _labels.size():
		return
	var label: Label = _labels[index]
	label.add_theme_font_override("font", FONT)
	label.add_theme_font_size_override("font_size", 10)
	if is_active:
		label.add_theme_color_override("font_color", Color("fde68a"))
		label.add_theme_constant_override("line_spacing", 0)
	else:
		label.add_theme_color_override("font_color", Color("a8a29e"))

func _apply_indicator_state(index: int, is_active: bool) -> void:
	if index >= _indicators.size():
		return
	var indicator: PanelContainer = _indicators[index]
	var style := StyleBoxFlat.new()
	if is_active:
		style.bg_color = Color(1.0, 1.0, 1.0, 0.08)
		var accent := StyleBoxFlat.new()
		accent.bg_color = Color("fde68a")
		accent.corner_radius_top_left = 2.0
		accent.corner_radius_top_right = 2.0
		accent.corner_radius_bottom_left = 2.0
		accent.corner_radius_bottom_right = 2.0
		indicator.add_theme_stylebox_override("panel", style)
		var bar: PanelContainer = _indicators[index].get_node_or_null("ActiveBar")
		if bar:
			bar.add_theme_stylebox_override("panel", accent)
	else:
		style.bg_color = Color(1.0, 1.0, 1.0, 0.0)
		indicator.add_theme_stylebox_override("panel", style)
		var bar: PanelContainer = _indicators[index].get_node_or_null("ActiveBar")
		if bar:
			bar.add_theme_stylebox_override("panel", StyleBoxEmpty.new())
