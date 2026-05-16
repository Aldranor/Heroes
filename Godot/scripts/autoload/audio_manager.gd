extends Node

const SFX_FILES: Dictionary = {
	"click": "res://assets/audio/sfx/click.wav",
	"hover": "res://assets/audio/sfx/hover.wav",
	"victory": "res://assets/audio/sfx/victory.wav",
	"defeat": "res://assets/audio/sfx/defeat.wav",
	"level_up": "res://assets/audio/sfx/level_up.wav",
	"coin": "res://assets/audio/sfx/coin.wav"
}

var _music_player: AudioStreamPlayer
var _music_tween: Tween
var _sfx_pool: Array[AudioStreamPlayer] = []
var _sfx_stream_cache: Dictionary = {}
var _fallback_sfx_cache: Dictionary = {}
var _music_tracks: Dictionary = {}

func _ready() -> void:
	process_mode = Node.PROCESS_MODE_ALWAYS
	_music_player = AudioStreamPlayer.new()
	_music_player.bus = "Music"
	_music_player.volume_db = linear_to_db(0.7)
	add_child(_music_player)

	for i: int in range(8):
		var player: AudioStreamPlayer = AudioStreamPlayer.new()
		player.bus = "SFX"
		add_child(player)
		_sfx_pool.append(player)

	_preload_sfx()

## Joue une musique par nom avec fondu.
func play_music(track_name: String, fade: float = 1.0) -> void:
	var stream: AudioStream = _resolve_music_track(track_name)
	if stream == null:
		push_warning("AudioManager.play_music: track introuvable: %s" % track_name)
		return
	_music_player.stream = stream
	_music_player.play()
	_fade_music_to_db(_music_player.volume_db, linear_to_db(_safe_volume_to_linear(0.7)), fade)

## Stoppe la musique avec fondu.
func stop_music(fade: float = 1.0) -> void:
	if not _music_player.playing:
		return
	var start_db: float = _music_player.volume_db
	_fade_music_to_db(start_db, -80.0, fade, true)

## Joue un effet sonore.
func play_sfx(sfx_name: String, volume_db: float = 0.0) -> void:
	var stream: AudioStream = _resolve_sfx_stream(sfx_name)
	if stream == null:
		push_warning("AudioManager.play_sfx: sfx introuvable: %s" % sfx_name)
		return
	var player: AudioStreamPlayer = _get_available_sfx_player()
	player.stream = stream
	player.volume_db = volume_db
	player.play()

## Règle le volume musique global (0.0 -> 1.0).
func set_music_volume(value: float) -> void:
	var bus_idx: int = AudioServer.get_bus_index("Music")
	if bus_idx >= 0:
		AudioServer.set_bus_volume_db(bus_idx, linear_to_db(_safe_volume_to_linear(value)))

## Règle le volume SFX global (0.0 -> 1.0).
func set_sfx_volume(value: float) -> void:
	var bus_idx: int = AudioServer.get_bus_index("SFX")
	if bus_idx >= 0:
		AudioServer.set_bus_volume_db(bus_idx, linear_to_db(_safe_volume_to_linear(value)))

func _preload_sfx() -> void:
	for sfx_name: String in SFX_FILES.keys():
		var resource_path: String = str(SFX_FILES[sfx_name])
		if ResourceLoader.exists(resource_path):
			var stream: AudioStream = load(resource_path)
			if stream != null:
				_sfx_stream_cache[sfx_name] = stream

func _resolve_sfx_stream(sfx_name: String) -> AudioStream:
	if _sfx_stream_cache.has(sfx_name):
		return _sfx_stream_cache[sfx_name]
	var resource_path: String = str(SFX_FILES.get(sfx_name, ""))
	if resource_path == "":
		return _get_fallback_sfx(sfx_name)
	if not ResourceLoader.exists(resource_path):
		return _get_fallback_sfx(sfx_name)
	var stream: AudioStream = load(resource_path)
	if stream != null:
		_sfx_stream_cache[sfx_name] = stream
		return stream
	return _get_fallback_sfx(sfx_name)

func _resolve_music_track(track_name: String) -> AudioStream:
	if _music_tracks.has(track_name):
		return _music_tracks[track_name]
	var path: String = "res://assets/audio/music/%s.ogg" % track_name
	if not ResourceLoader.exists(path):
		path = "res://assets/audio/music/%s.wav" % track_name
	if not ResourceLoader.exists(path):
		return null
	var stream: AudioStream = load(path)
	if stream != null:
		_music_tracks[track_name] = stream
	return stream

func _get_available_sfx_player() -> AudioStreamPlayer:
	for player: AudioStreamPlayer in _sfx_pool:
		if not player.playing:
			return player
	return _sfx_pool[0]

func _fade_music_to_db(from_db: float, to_db: float, duration: float, stop_after: bool = false) -> void:
	if _music_tween != null:
		_music_tween.kill()
	_music_player.volume_db = from_db
	if duration <= 0.0:
		_music_player.volume_db = to_db
		if stop_after:
			_music_player.stop()
		return
	_music_tween = create_tween()
	_music_tween.tween_property(_music_player, "volume_db", to_db, duration)
	if stop_after:
		_music_tween.finished.connect(_music_player.stop, CONNECT_ONE_SHOT)

func _safe_volume_to_linear(value: float) -> float:
	return clampf(value, 0.001, 1.0)

func _get_fallback_sfx(sfx_name: String) -> AudioStream:
	if _fallback_sfx_cache.has(sfx_name):
		return _fallback_sfx_cache[sfx_name]
	var stream: AudioStreamWAV = _build_tone_for_name(sfx_name)
	_fallback_sfx_cache[sfx_name] = stream
	return stream

func _build_tone_for_name(sfx_name: String) -> AudioStreamWAV:
	match sfx_name:
		"hover":
			return _build_tone_stream(900.0, 0.04)
		"victory":
			return _build_tone_stream(1200.0, 0.14)
		"defeat":
			return _build_tone_stream(280.0, 0.14)
		"level_up":
			return _build_tone_stream(1000.0, 0.12)
		"coin":
			return _build_tone_stream(1500.0, 0.07)
		_:
			return _build_tone_stream(700.0, 0.05)

func _build_tone_stream(frequency: float, duration: float) -> AudioStreamWAV:
	var mix_rate: int = 22050
	var sample_count: int = max(1, int(mix_rate * duration))
	var data: PackedByteArray = PackedByteArray()
	data.resize(sample_count)
	for i: int in range(sample_count):
		var t: float = float(i) / float(mix_rate)
		var sample: float = sin(TAU * frequency * t) * 0.45
		var unsigned_sample: int = int((sample + 1.0) * 127.5)
		data[i] = clampi(unsigned_sample, 0, 255)
	var wav: AudioStreamWAV = AudioStreamWAV.new()
	wav.format = AudioStreamWAV.FORMAT_8_BITS
	wav.mix_rate = mix_rate
	wav.stereo = false
	wav.data = data
	return wav
