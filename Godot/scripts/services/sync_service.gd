extends Node
class_name SyncService

const QUEUE_STORAGE_KEY: String = "sync_queue"
const SYNC_ENDPOINT: String = "/sync/actions"

var _pending_queue: Array[Dictionary] = []
var _api_client: Node = ApiClient

func _ready() -> void:
	_load_queue()

## Injecte un client API (utile pour les tests unitaires).
func set_api_client(api_client: Node) -> void:
	_api_client = api_client

## Ajoute une action à la file de synchronisation offline.
func queue_action(action_type: String, payload: Dictionary) -> void:
	var entry: Dictionary = {
		"id": "%s_%s" % [str(Time.get_unix_time_from_system()), str(randi())],
		"action_type": action_type,
		"payload": payload,
		"created_at_unix": Time.get_unix_time_from_system()
	}
	_pending_queue.append(entry)
	_persist_queue()

## Traite la file en attente lorsque la connexion revient.
func process_pending_queue() -> void:
	if _pending_queue.is_empty():
		return
	if not bool(_api_client.get("is_online")):
		return

	var index: int = 0
	while index < _pending_queue.size():
		var action: Dictionary = _pending_queue[index]
		var body: Dictionary = {
			"action_type": str(action.get("action_type", "")),
			"payload": action.get("payload", {}),
			"client_action_id": str(action.get("id", "")),
			"client_timestamp": action.get("created_at_unix", Time.get_unix_time_from_system())
		}
		var response: ApiResponse = await _api_client.post(SYNC_ENDPOINT, body)
		if response.success:
			_pending_queue.remove_at(index)
			_persist_queue()
			continue
		if response.is_offline:
			break
		index += 1

func _load_queue() -> void:
	var stored: Variant = LocalStorage.load_data(QUEUE_STORAGE_KEY, [])
	if typeof(stored) != TYPE_ARRAY:
		_pending_queue = []
		return
	_pending_queue = []
	for item: Variant in stored:
		if typeof(item) == TYPE_DICTIONARY:
			_pending_queue.append(item)

func _persist_queue() -> void:
	LocalStorage.save(QUEUE_STORAGE_KEY, _pending_queue)
