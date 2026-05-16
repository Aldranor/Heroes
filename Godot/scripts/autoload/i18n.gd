extends Node

## Centralized internationalization autoload.
## Usage: I18n.t("Bienvenue") returns the translation for the current locale.
##
## For .tscn auto-translation: add the string as a key in translations.csv.
## Strings in .tscn files (text, placeholder_text, etc.) are auto-translated
## when the Translation resource is loaded and the key matches exactly.

enum Locale {
	FR,
	EN
}

const LOCALE_KEY := "i18n_locale"

var _locale: Locale = Locale.FR

# --- Public API ---

## Returns the translated string for the given key.
## Falls back to the key itself if no translation is found.
func t(key: String) -> String:
	var translated: String = tr(key)
	if translated == key or translated.is_empty():
		return key
	return translated

## Returns the translated string with format arguments.
## Example: I18n.tf("Niv. %d", [5]) → "Lv. 5" (en) or "Niv. 5" (fr)
func tf(key: String, args: Array) -> String:
	return t(key) % args

## Returns the current locale.
func locale() -> Locale:
	return _locale

## Returns the current locale as a string tag ("fr" or "en").
func locale_tag() -> String:
	return "fr" if _locale == Locale.FR else "en"

## Sets the locale and persists it.
func set_locale(value: Locale) -> void:
	_locale = value
	TranslationServer.set_locale(locale_tag())
	LocalStorage.save(LOCALE_KEY, int(value))

# --- Internal ---

func _ready() -> void:
	_load_persisted_locale()


func _load_persisted_locale() -> void:
	var saved: int = LocalStorage.load_data(LOCALE_KEY, -1)
	if saved >= 0 and saved < Locale.size():
		_locale = saved as Locale
	TranslationServer.set_locale(locale_tag())
