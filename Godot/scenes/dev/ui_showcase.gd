extends Control

@onready var _scroll: ScrollContainer = %Scroll
@onready var _content_vbox: VBoxContainer = %ContentVBox
@onready var _dialog_box: AppDialogBox = %DialogBox
@onready var _tab_bar: AppTabBar = %BottomTabBar
@onready var _reward_popup: AppRewardPopup = %RewardPopup
@onready var _choice_dialog: AppChoiceDialog = %ChoiceDialog
@onready var _transition: AppSceneTransition = %Transition
@onready var _flashcard: AppFlashcard = %FlashcardDemo
@onready var _tabs_demo: AppTabs = %TabsDemo
@onready var _screen_header: AppScreenHeader = %ScreenHeader

func _ready() -> void:
	_scroll.resized.connect(_on_scroll_resized)
	Callable(self, "_reset_scroll_x").call_deferred()
	_fit_content_width()

	_setup_dialogs()
	_setup_components()
	_connect_buttons()

func _setup_dialogs() -> void:
	_dialog_box.character_name = "Moniteur"
	_dialog_box.dialog_text = "Bienvenue sur Heroes Academy. Teste chaque composant tactile."
	_dialog_box.continued.connect(_on_dialog_finished)

	_reward_popup.hide()
	_choice_dialog.hide()

func _setup_components() -> void:
	_screen_header.show_back_button = false

	_flashcard.setup("Quelle est la capitale de la France ?", "Paris")
	_tabs_demo.set_tabs(["Hub", "Cartes", "Combat", "Sac"])

func _connect_buttons() -> void:
	for sig in [
		{"node": %ToastButton, "fn": _on_toast_pressed},
		{"node": %ModalButton, "fn": _on_modal_pressed},
		{"node": %LoadingButton, "fn": _on_loading_pressed},
		{"node": %RewardButton, "fn": _on_reward_pressed},
		{"node": %ChoiceButton, "fn": _on_choice_pressed},
		{"node": %TransitionButton, "fn": _on_transition_pressed},
	]:
		(sig["node"] as Button).pressed.connect(sig["fn"])

	_tab_bar.tab_changed.connect(_on_tab_changed)
	_tabs_demo.tab_changed.connect(func(i): AppToast.show_toast("Tab %d" % (i + 1), AppToast.Type.INFO))

func _on_scroll_resized() -> void:
	_fit_content_width()

func _fit_content_width() -> void:
	_content_vbox.custom_minimum_size.x = max(0.0, _scroll.size.x - 12.0)

func _reset_scroll_x() -> void:
	_scroll.scroll_horizontal = 0

func _on_dialog_finished() -> void:
	pass

func _on_tab_changed(tab_index: int) -> void:
	AppToast.show_toast("Onglet %d" % (tab_index + 1), AppToast.Type.INFO)

func _on_toast_pressed() -> void:
	AppToast.show_toast("AppToast SUCCESS", AppToast.Type.SUCCESS)

func _on_modal_pressed() -> void:
	var ok := await AppModal.confirm("Quitter le combat ?", "Action non réversible.")
	if ok:
		AppToast.show_toast("Confirmé", AppToast.Type.SUCCESS)
	else:
		AppToast.show_toast("Annulé", AppToast.Type.WARNING)

func _on_loading_pressed() -> void:
	LoadingOverlay.show("Chargement...")
	await get_tree().create_timer(1.4).timeout
	LoadingOverlay.hide()

func _on_reward_pressed() -> void:
	_reward_popup.show_reward("Nouveau skill !", "Tu as appris Attaque foudroyante", "⚡")
	_reward_popup.show()
	await _reward_popup.dismissed

func _on_choice_pressed() -> void:
	_choice_dialog.show_choice("Que faire ?", "Choisis ta prochaine action", ["Explorer", "Se reposer", "Fuir"])
	_choice_dialog.selected_index = -1
	_choice_dialog.show()
	await _choice_dialog.choice_selected
	_choice_dialog.hide()

func _on_transition_pressed() -> void:
	await _transition.fade_in(0.3)
	await get_tree().create_timer(0.5).timeout
	await _transition.fade_out(0.3)
