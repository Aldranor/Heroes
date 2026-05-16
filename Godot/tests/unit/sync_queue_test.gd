extends "res://addons/gut/test.gd"

const SyncServiceScript = preload("res://scripts/services/sync_service.gd")

class FakeApiClient extends Node:
	var is_online: bool = true
	var post_call_count: int = 0
	var should_return_offline: bool = false

	func post(_path: String, _payload: Dictionary) -> ApiResponse:
		post_call_count += 1
		await get_tree().process_frame
		if should_return_offline:
			return ApiResponse.new(false, 0, null, {}, "offline", true, PackedByteArray())
		return ApiResponse.new(true, 200, {"ok": true}, {}, "ok", false, PackedByteArray())

var _sync_service: SyncService
var _fake_api_client: FakeApiClient

func before_each() -> void:
	LocalStorage.clear_all()
	_sync_service = SyncServiceScript.new()
	_fake_api_client = FakeApiClient.new()
	add_child(_sync_service)
	add_child(_fake_api_client)
	_sync_service.set_api_client(_fake_api_client)

func after_each() -> void:
	LocalStorage.clear_all()
	if is_instance_valid(_sync_service):
		_sync_service.free()
	if is_instance_valid(_fake_api_client):
		_fake_api_client.free()

func test_queue_action_persists_to_local_storage() -> void:
	_sync_service.queue_action("battle_completed", {"xp": 25})
	var queue_data: Variant = LocalStorage.load_data("sync_queue", [])
	assert_eq(queue_data.size(), 1)
	assert_eq(queue_data[0]["action_type"], "battle_completed")

func test_process_pending_queue_dequeues_on_success() -> void:
	_sync_service.queue_action("battle_completed", {"xp": 25})
	await _sync_service.process_pending_queue()
	var queue_data: Variant = LocalStorage.load_data("sync_queue", [])
	assert_eq(_fake_api_client.post_call_count, 1)
	assert_eq(queue_data.size(), 0)
