<?php

namespace App\Http\Controllers;

use App\Enums\TrainingSessionStatus;
use App\Enums\TrainingMode;
use App\Models\Training\TrainingSession;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class TrainingController extends Controller
{
    public function create(Request $request, TrainingService $trainingService): View
    {
        return view('training.create', $trainingService->selectionOptions($request->user()));
    }

    public function store(Request $request, TrainingService $trainingService): RedirectResponse
    {
        $validated = $request->validate([
            'learning_domain_id' => ['required', 'integer', 'exists:learning_domains,id'],
            'learning_category_id' => ['required', 'integer', 'exists:learning_categories,id'],
            'user_companion_id' => ['required', 'integer', 'exists:user_companions,id'],
            'adventure_node_id' => ['nullable', 'integer', 'exists:adventure_nodes,id'],
            'mode' => ['nullable', 'string', 'in:'.implode(',', array_column(TrainingMode::cases(), 'value'))],
        ]);

        try {
            $trainingSession = $trainingService->startSessionFromSelection($request->user(), $validated);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['training' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('training.show', $trainingSession);
    }

    public function show(Request $request, TrainingSession $trainingSession, TrainingService $trainingService): View|RedirectResponse
    {
        $this->authorizeSession($request, $trainingSession);

        if ($trainingSession->status === TrainingSessionStatus::Completed) {
            return redirect()->route('training.result', $trainingSession);
        }

        $trainingSession->loadMissing(['learningDomain', 'learningCategory', 'userCompanion.companion', 'adventureNode.map.world']);

        return view('training.show', [
            'trainingSession' => $trainingSession,
            'question' => $trainingService->currentQuestion($trainingSession),
            'answeredCount' => $trainingService->answeredCount($trainingSession),
            'readyForResult' => $trainingService->readyForResult($trainingSession),
            'feedback' => $trainingService->feedbackForAnswer($trainingSession, $request->integer('feedback')),
        ]);
    }

    public function answer(Request $request, TrainingSession $trainingSession, TrainingService $trainingService): RedirectResponse
    {
        $this->authorizeSession($request, $trainingSession);

        $validated = $request->validate([
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'use_hint' => ['nullable', 'boolean'],
            'use_extra_time' => ['nullable', 'boolean'],
            'use_remove_choice' => ['nullable', 'boolean'],
            'use_bonus_xp' => ['nullable', 'boolean'],
        ]);

        try {
            $trainingAnswer = $trainingService->submitCurrentAnswer(
                $trainingSession,
                (int) $validated['answer_id'],
                $validated['time_spent_seconds'] ?? null,
                [
                    'hint' => (bool) ($validated['use_hint'] ?? false),
                    'extra_time' => (bool) ($validated['use_extra_time'] ?? false),
                    'remove_choice' => (bool) ($validated['use_remove_choice'] ?? false),
                    'bonus_xp' => (bool) ($validated['use_bonus_xp'] ?? false),
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['training' => $exception->getMessage()]);
        }

        return redirect()->route('training.show', [
            'trainingSession' => $trainingSession,
            'feedback' => $trainingAnswer->id,
        ]);
    }

    public function result(Request $request, TrainingSession $trainingSession, TrainingService $trainingService): View|RedirectResponse
    {
        $this->authorizeSession($request, $trainingSession);

        if (! $trainingService->readyForResult($trainingSession)) {
            return redirect()->route('training.show', $trainingSession);
        }

        $trainingSession = $trainingService->completeSession($trainingSession);
        $trainingSession->loadMissing(['learningDomain', 'learningCategory', 'userCompanion.companion', 'adventureNode.map.world']);

        return view('training.result', [
            'trainingSession' => $trainingSession,
            'reviewAnswers' => $trainingService->reviewAnswers($trainingSession),
        ]);
    }

    private function authorizeSession(Request $request, TrainingSession $trainingSession): void
    {
        abort_unless($trainingSession->user_id === $request->user()->id, 404);
    }
}
