<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Learning\Answer;
use App\Models\Learning\Question;
use Illuminate\Support\Collection;

class QuestionPresentationService
{
    public function decorateCollection(iterable $questions): Collection
    {
        return collect($questions)->map(fn (Question $question): Question => $this->decorate($question));
    }

    public function decorate(Question $question): Question
    {
        $metadata = $question->metadata ?? [];
        $type = $question->type ?? QuestionType::Definition;

        $question->setAttribute('ui_mode_key', $type->value);
        $question->setAttribute('ui_mode_label', $this->modeLabel($type));
        $question->setAttribute('ui_timer_seconds', (int) ($metadata['timer_seconds'] ?? $this->defaultTimer($type)));
        $question->setAttribute('ui_context', $metadata['context'] ?? null);
        $question->setAttribute('ui_hint', $metadata['hint'] ?? null);
        $question->setAttribute('ui_has_traps', (bool) ($metadata['has_traps'] ?? false));
        $question->setAttribute('ui_prompt_media', $this->promptMedia($question, $type, $metadata));
        $question->setAttribute('ui_answer_layout', $this->answerLayout($type, $metadata));

        $question->answers->each(function (Answer $answer) use ($type): void {
            $answerMetadata = $answer->metadata ?? [];

            $answer->setAttribute('ui_image', $answer->image ?: ($answerMetadata['image'] ?? null));
            $answer->setAttribute('ui_badge', $answerMetadata['badge'] ?? null);
            $answer->setAttribute('ui_note', $answerMetadata['note'] ?? null);
            $answer->setAttribute('ui_accent', $answerMetadata['accent'] ?? null);
            $answer->setAttribute('ui_is_visual', in_array($type, [QuestionType::ReverseLookup], true) || $answer->getAttribute('ui_image') !== null);
        });

        return $question;
    }

    protected function modeLabel(QuestionType $type): string
    {
        return match ($type) {
            QuestionType::VisualRecognition => 'Reconnaissance visuelle',
            QuestionType::Association => 'Association',
            QuestionType::Definition => 'Définition',
            QuestionType::Situation => 'Situation',
            QuestionType::ReverseLookup => 'À identifier',
        };
    }

    protected function defaultTimer(QuestionType $type): int
    {
        return match ($type) {
            QuestionType::VisualRecognition, QuestionType::ReverseLookup => 18,
            QuestionType::Association, QuestionType::Definition => 22,
            QuestionType::Situation => 28,
        };
    }

    protected function answerLayout(QuestionType $type, array $metadata): string
    {
        return $metadata['answer_layout'] ?? match ($type) {
            QuestionType::ReverseLookup => 'visual-grid',
            QuestionType::Association => 'compact-grid',
            default => 'stack',
        };
    }

    protected function promptMedia(Question $question, QuestionType $type, array $metadata): ?array
    {
        $promptMedia = $metadata['prompt_media'] ?? null;
        $visualToken = $metadata['visual_token'] ?? null;

        if (is_array($promptMedia)) {
            return array_merge([
                'kind' => 'tile',
                'title' => $question->learningCategory?->name,
                'subtitle' => null,
                'value' => null,
                'image' => null,
                'accent' => $question->learningCategory?->color,
            ], $promptMedia);
        }

        if (! in_array($type, [QuestionType::VisualRecognition, QuestionType::ReverseLookup, QuestionType::Association], true)) {
            return null;
        }

        if (! is_string($visualToken) || trim($visualToken) === '') {
            return null;
        }

        return [
            'kind' => 'token',
            'title' => $question->learningCategory?->name,
            'subtitle' => $type === QuestionType::Association ? 'Classez le bon repere' : 'Repere visuel',
            'value' => $visualToken,
            'image' => null,
            'accent' => $question->learningCategory?->color,
        ];
    }
}
