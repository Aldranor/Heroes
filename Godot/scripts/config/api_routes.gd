extends Object
class_name ApiRoutes

## Lessons
const LESSONS_INDEX: String = "/api/lessons"
const LESSON_SHOW: String = "/api/lessons/{id}"
const LESSON_FLASHCARD: String = "/api/lessons/{id}/flashcard"
const LESSON_QUIZ: String = "/api/lessons/{id}/quiz"

## Missions / Adventure
const MISSIONS_INDEX: String = "/api/missions"
const MISSIONS_DIALOGUE_ADVANCE: String = "/api/missions/{id}/dialogue/advance"

## Skills
const SKILLS_INDEX: String = "/api/skills"
const SKILL_UNLOCK: String = "/api/skills/{slug}/unlock"
const SKILL_RESPEC: String = "/api/skills/respec"

## Training
const TRAINING_OPTIONS: String = "/api/training/options"
const TRAINING_START: String = "/api/training/start"
const TRAINING_SHOW: String = "/api/training/{id}"
const TRAINING_ANSWER: String = "/api/training/{id}/answer"
const TRAINING_RESULT: String = "/api/training/{id}/result"

## Combat
const COMBAT_PREPARE: String = "/api/combat/{mission_id}/prepare"
const COMBAT_START: String = "/api/combat/{mission_id}/start"
const COMBAT_SHOW: String = "/api/combat/{id}"
const COMBAT_ACTION: String = "/api/combat/{id}/action"

## Shop
const SHOP_INDEX: String = "/api/shop"
const SHOP_BUY: String = "/api/shop/{slug}/buy"

## Echo Hall
const ECHO_HALL_INDEX: String = "/api/echo-hall"
