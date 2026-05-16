extends Node

const BODY_OPTIONS: Array[Dictionary] = [
	{"key": "human-female-01", "label": "Humaine 1", "category": "feminin"},
	{"key": "human-female-02", "label": "Humaine 2", "category": "feminin"},
	{"key": "human-female-03", "label": "Humaine 3", "category": "feminin"},
	{"key": "human-female-04", "label": "Humaine 4", "category": "feminin"},
	{"key": "human-male-01", "label": "Humain 1", "category": "masculin"},
	{"key": "human-male-02", "label": "Humain 2", "category": "masculin"},
	{"key": "human-male-03", "label": "Humain 3", "category": "masculin"},
	{"key": "human-male-04", "label": "Humain 4", "category": "masculin"},
	{"key": "human-neutral-01", "label": "Neutre 1", "category": "neutre"},
	{"key": "human-neutral-02", "label": "Neutre 2", "category": "neutre"},
	{"key": "human-neutral-03", "label": "Neutre 3", "category": "neutre"},
	{"key": "human-neutral-04", "label": "Neutre 4", "category": "neutre"},
]

const HAIR_OPTIONS: Array[Dictionary] = [
	{"key": "short-01", "label": "Court 1"},
	{"key": "short-02", "label": "Court 2"},
	{"key": "long-01", "label": "Long 1"},
	{"key": "long-02", "label": "Long 2"},
	{"key": "curly-01", "label": "Bouclé 1"},
	{"key": "curly-02", "label": "Bouclé 2"},
	{"key": "spiky-01", "label": "Piquant 1"},
	{"key": "spiky-02", "label": "Piquant 2"},
	{"key": "ponytail-01", "label": "Queue 1"},
	{"key": "ponytail-02", "label": "Queue 2"},
]

const OUTFIT_OPTIONS: Array[Dictionary] = [
	{
		"key": "casual",
		"label": "Décontracté",
		"description": "Look relax du quotidien",
		"color": Color("6b8e23")
	},
	{
		"key": "conducteur",
		"label": "Conducteur",
		"description": "Tenue professionnelle de conduite",
		"color": Color("4682b4")
	},
	{
		"key": "sport",
		"label": "Sport",
		"description": "Athlétique et dynamique",
		"color": Color("cd5c5c")
	},
	{
		"key": "elegant",
		"label": "Élégant",
		"description": "Chic et sophistiqué",
		"color": Color("6a0dad")
	},
	{
		"key": "voyageur",
		"label": "Voyageur",
		"description": "Prêt pour l'aventure",
		"color": Color("d2691e")
	},
	{
		"key": "mecanicien",
		"label": "Mécanicien",
		"description": "Combinaison de travail",
		"color": Color("2f4f4f")
	},
]

const SKIN_COLORS: Array[Color] = [
	Color("f5d0a9"),
	Color("e8b88a"),
	Color("d4a574"),
	Color("b8845c"),
	Color("9c6b4a"),
	Color("7a5238"),
	Color("5a3d28"),
	Color("3d2818"),
]

const HAIR_COLORS: Array[Color] = [
	Color("1a1a1a"),
	Color("3a2a1a"),
	Color("5a3a1a"),
	Color("8b5e3c"),
	Color("c4a265"),
	Color("d4a017"),
	Color("8b0000"),
	Color("4a0080"),
	Color("2e8b57"),
	Color("c0c0c0"),
]

const STARTER_COMPANIONS: Array[Dictionary] = [
	{
		"slug": "gardien-des-panneaux",
		"name": "Gardien des Panneaux",
		"category": "signs",
		"rarity": "common",
		"sprite_path": "res://assets/sprites/companions/gardien.png",
		"stats": {"hp": 92, "attack": 16, "defense": 18, "speed": 12},
		"description": "Calme et protecteur. Il encaisse les erreurs des jeunes conducteurs.",
		"personality": "Calme et protecteur",
		"color": Color("4682b4")
	},
	{
		"slug": "strateges-des-priorites",
		"name": "Stratège des Priorités",
		"category": "priority",
		"rarity": "common",
		"sprite_path": "res://assets/sprites/companions/strateges.png",
		"stats": {"hp": 78, "attack": 22, "defense": 14, "speed": 16},
		"description": "Réfléchi et méthodique. Il analyse chaque intersection avant d'agir.",
		"personality": "Réfléchi et méthodique",
		"color": Color("6a0dad")
	},
	{
		"slug": "eclaireur-de-vitesse",
		"name": "Éclaireur de Vitesse",
		"category": "speed",
		"rarity": "common",
		"sprite_path": "res://assets/sprites/companions/eclaireur.png",
		"stats": {"hp": 65, "attack": 28, "defense": 10, "speed": 24},
		"description": "Rapide et audacieux. Il fonce et apprend de ses erreurs à toute vitesse.",
		"personality": "Rapide et audacieux",
		"color": Color("cd5c5c")
	},
]

static func get_body_options() -> Array[Dictionary]:
	return BODY_OPTIONS.duplicate()

static func get_hair_options() -> Array[Dictionary]:
	return HAIR_OPTIONS.duplicate()

static func get_outfit_options() -> Array[Dictionary]:
	return OUTFIT_OPTIONS.duplicate()

static func get_skin_colors() -> Array[Color]:
	return SKIN_COLORS.duplicate()

static func get_hair_colors() -> Array[Color]:
	return HAIR_COLORS.duplicate()

static func get_starter_companions() -> Array[Dictionary]:
	var result: Array[Dictionary] = []
	for c: Dictionary in STARTER_COMPANIONS:
		result.append(c.duplicate(true))
	return result

static func get_default_body_key() -> String:
	return "human-female-01"

static func get_default_hair_key() -> String:
	return "short-01"

static func get_default_outfit_key() -> String:
	return "casual"

static func get_default_skin_color() -> Color:
	return SKIN_COLORS[0]

static func get_default_hair_color() -> Color:
	return HAIR_COLORS[0]

static func get_default_companion_slug() -> String:
	return STARTER_COMPANIONS[0]["slug"]

static func get_rarity_color(rarity: String) -> Color:
	match rarity.to_lower():
		"common":
			return Color("c0c0c0")
		"uncommon":
			return Color("2e8b57")
		"rare":
			return Color("4682b4")
		"epic":
			return Color("6a0dad")
		"legendary":
			return Color("d4af37")
		_:
			return Color("c0c0c0")
