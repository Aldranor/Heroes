extends Node

const USER_MODEL = preload("res://scripts/models/user.gd")
const GAME_SETTINGS_MODEL = preload("res://scripts/models/game_settings.gd")

const USER_CACHE_KEY: String = "cache_user"
const SETTINGS_CACHE_KEY: String = "cache_settings"
const TOKEN_STORAGE_KEY: String = "secure_auth_token"

signal user_changed(user: User)
signal companions_changed()
signal inventory_changed()
signal coins_changed(new_amount: int)
signal level_up(new_level: int)
@warning_ignore("unused_signal")
signal battle_started(battle: BattleState)
@warning_ignore("unused_signal")
signal battle_ended(result: Dictionary)

var current_user: User = USER_MODEL.new()
var active_companions: Array[UserCompanion] = []
var owned_companions: Array[UserCompanion] = []
var inventory: Array[Equipment] = []
var active_skills: Array[Skill] = []
var current_battle: BattleState = null
var settings: GameSettings = GAME_SETTINGS_MODEL.new()

func _ready() -> void:
	_load_settings_from_cache()
	if _has_auth_token():
		load_user_data()

## Charge les donnees utilisateur (cache local puis API /me via service).
func load_user_data() -> void:
	_load_user_data_async()

## Reinitialise tout le state global (deconnexion).
func clear() -> void:
	current_user = USER_MODEL.new()
	active_companions.clear()
	owned_companions.clear()
	inventory.clear()
	active_skills.clear()
	current_battle = null
	settings = GAME_SETTINGS_MODEL.new()
	LocalStorage.delete(USER_CACHE_KEY)
	LocalStorage.delete(SETTINGS_CACHE_KEY)
	emit_signal("user_changed", current_user)
	emit_signal("companions_changed")
	emit_signal("inventory_changed")
	emit_signal("coins_changed", current_user.coins)

## Met a jour les coins et notifie les observateurs.
func update_coins(delta: int) -> void:
	current_user.coins = max(0, current_user.coins + delta)
	_save_user_to_cache()
	emit_signal("coins_changed", current_user.coins)
	emit_signal("user_changed", current_user)

## Met a jour l'XP et gere les level up.
func update_xp(delta: int) -> void:
	current_user.xp = max(0, current_user.xp + delta)
	while current_user.xp >= current_user.xp_to_next_level:
		current_user.xp -= current_user.xp_to_next_level
		current_user.level += 1
		current_user.xp_to_next_level = _compute_xp_to_next_level(current_user.level)
		emit_signal("level_up", current_user.level)
	_save_user_to_cache()
	emit_signal("user_changed", current_user)

## Ajoute un compagnon a la collection du joueur.
func grant_companion(uc: UserCompanion) -> void:
	if uc == null:
		return
	if not _has_user_companion(owned_companions, uc.id):
		owned_companions.append(uc)
	if uc.is_active and not _has_user_companion(active_companions, uc.id):
		active_companions.append(uc)
	emit_signal("companions_changed")

## Hydrate current_user depuis un dictionnaire.
func set_current_user_from_dict(data: Dictionary) -> void:
	current_user = USER_MODEL.new().from_dict(data)
	_save_user_to_cache()
	emit_signal("coins_changed", current_user.coins)
	emit_signal("user_changed", current_user)

func _load_user_data_async() -> void:
	_load_user_from_cache()
	var response: ApiResponse = await AuthService.refresh_user()
	if response.success:
		if current_user.id == 0:
			var payload: Dictionary = _extract_payload_dict(response.data)
			if not payload.is_empty():
				set_current_user_from_dict(payload)
	elif response.is_offline:
		EventBus.emit_signal("low_connection_warning")

func _load_user_from_cache() -> void:
	var cached: Variant = LocalStorage.load_data(USER_CACHE_KEY, {})
	if cached is Dictionary and not cached.is_empty():
		current_user = USER_MODEL.new().from_dict(cached)
		emit_signal("coins_changed", current_user.coins)
		emit_signal("user_changed", current_user)

func _save_user_to_cache() -> void:
	LocalStorage.save(USER_CACHE_KEY, current_user.to_dict())

func _load_settings_from_cache() -> void:
	var cached: Variant = LocalStorage.load_data(SETTINGS_CACHE_KEY, {})
	if cached is Dictionary and not cached.is_empty():
		settings = GAME_SETTINGS_MODEL.new().from_dict(cached)
	else:
		settings = GAME_SETTINGS_MODEL.new()

func _save_settings_to_cache() -> void:
	LocalStorage.save(SETTINGS_CACHE_KEY, settings.to_dict())

func _extract_payload_dict(data: Variant) -> Dictionary:
	if data is Dictionary:
		var dict_data: Dictionary = data
		if dict_data.has("data") and dict_data["data"] is Dictionary:
			return dict_data["data"]
		return dict_data
	return {}

func _has_auth_token() -> bool:
	return str(LocalStorage.load_data(TOKEN_STORAGE_KEY, "")) != ""

func _compute_xp_to_next_level(level: int) -> int:
	return 100 + (max(level - 1, 0) * 25)

func _has_user_companion(source: Array[UserCompanion], user_companion_id: int) -> bool:
	for companion: UserCompanion in source:
		if companion.id == user_companion_id:
			return true
	return false
