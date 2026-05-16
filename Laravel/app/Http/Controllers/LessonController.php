<?php

namespace App\Http\Controllers;

use App\Models\Lessons\Flashcard;
use App\Models\Lessons\Lesson;
use App\Services\LessonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class LessonController extends Controller
{
    public function index(): View
    {
        return view('lessons.index');
    }

    public function show(Request $request, Lesson $lesson, LessonService $lessonService): View
    {
        try {
            return view('lessons.show', $lessonService->lessonData($request->user(), $lesson));
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }

    public function flashcard(
        Request $request,
        Lesson $lesson,
        Flashcard $flashcard,
        LessonService $lessonService,
    ): RedirectResponse {
        try {
            $lessonService->markFlashcardViewed(
                $request->user(),
                $lesson,
                $flashcard,
                $request->boolean('mastered'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['lesson' => $exception->getMessage()]);
        }

        return redirect()->to(route('lessons.show', [
            'lesson' => $lesson,
            'card' => max(0, (int) $request->input('card', 0)),
        ]).'#flashcards');
    }

    public function quiz(Request $request, Lesson $lesson, LessonService $lessonService): RedirectResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'exists:answers,id'],
        ]);

        try {
            $progress = $lessonService->submitQuiz($request->user(), $lesson, $validated['answers']);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['lesson' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->to(route('lessons.show', $lesson).'#'.($progress->quiz_passed ? 'summary' : 'mini-quiz'))
            ->with('lesson_quiz_result', $progress->quiz_passed ? 'passed' : 'failed');
    }
}
