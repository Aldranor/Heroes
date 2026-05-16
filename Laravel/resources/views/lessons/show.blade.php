<x-layouts::app :title="$lesson->title">
    @php
        $sectionTone = [
            'introduction'     => 'border-sky-200/20 bg-sky-300/10 text-sky-100',
            'key_concepts'     => 'border-amber-200/20 bg-amber-300/10 text-amber-100',
            'concrete_example' => 'border-emerald-200/20 bg-emerald-300/10 text-emerald-100',
            'summary'          => 'border-stone-200/20 bg-stone-300/10 text-stone-100',
        ];
        $trainingUrl = $lesson->learning_category_id
            ? route('training.create', ['learning_domain_id' => $lesson->learning_domain_id, 'learning_category_id' => $lesson->learning_category_id])
            : route('training.create');

        $totalFlashcards = $lesson->flashcards->count();
        $masteredFlashcards = $lesson->flashcards->filter(
            fn ($f) => in_array($f->getAttribute('deck_status_key'), ['mastered', 'automated'], true),
        )->count();
        $remainingFlashcards = max(0, $totalFlashcards - $masteredFlashcards);
        $flashcardProgressPercent = $totalFlashcards > 0
            ? (int) round(($viewedFlashcards / $totalFlashcards) * 100)
            : 0;

        $quizQuestionCount = $lesson->questions->count();
        $quizPassTarget = $quizQuestionCount > 0
            ? (int) ceil(($lesson->quiz_pass_score / 100) * $quizQuestionCount)
            : 0;

        // Where the user is in the journey
        $pathCurrentKey = match (true) {
            $progress->quiz_passed => 'training',
            $quizQuestionCount > 0 && ($totalFlashcards === 0 || $viewedFlashcards >= $totalFlashcards) => 'quiz',
            $viewedFlashcards > 0 => 'flashcards',
            default => 'lesson',
        };
        $pathAnchors = [
            'lesson'     => '#lesson-content',
            'flashcards' => '#flashcards',
            'quiz'       => '#mini-quiz',
            'training'   => $progress->quiz_passed ? $trainingUrl : '#lesson-content',
        ];
        $currentActionLabel = match ($pathCurrentKey) {
            'lesson'     => __('lessons.show.start_reading'),
            'flashcards' => __('lessons.show.continue_to_flashcards'),
            'quiz'       => __('lessons.show.continue_to_quiz'),
            'training'   => __('lessons.show.start_training'),
        };

        // Section tabs (only those that actually have content)
        $tabs = collect([
            ['key' => 'lesson',     'href' => '#lesson-content', 'label' => __('lessons.show.reading'),     'show' => $lesson->sections->isNotEmpty()],
            ['key' => 'flashcards', 'href' => '#flashcards',     'label' => __('lessons.show.flashcards'),  'show' => $totalFlashcards > 0],
            ['key' => 'quiz',       'href' => '#mini-quiz',      'label' => __('lessons.show.mini_quiz'),   'show' => $quizQuestionCount > 0],
        ])->where('show', true)->values();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-3 sm:gap-4">

        <section class="rs-panel relative overflow-hidden rounded-[1.6rem] p-4 sm:rounded-[2rem] sm:p-6">
            <img src="{{ $lesson->hero_image ? asset($lesson->hero_image) : asset('images/backgrounds/cours.png') }}"
                alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-b from-stone-950/85 via-stone-950/85 to-stone-950/95"></div>

            <div class="relative">
                <a href="{{ route('lessons.index') }}"
                    class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.16em] text-stone-400 transition hover:text-stone-100">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ __('lessons.show.back') }}
                </a>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-amber-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-200"></span>
                        Mission de guilde
                    </span>
                    @if ($lesson->mentor_name)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-stone-300">
                            Mentor · {{ $lesson->mentor_name }}
                        </span>
                    @endif
                </div>
                <p class="rs-label mt-4">{{ __('lessons.show.eyebrow') }}</p>
                <h1 class="rs-display mt-2 text-[1.9rem] leading-tight text-stone-50 sm:text-4xl">{{ $lesson->title }}</h1>
                <p class="mt-3 text-sm leading-6 text-stone-300">{{ $lesson->summary }}</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-300">
                        {{ $lesson->sections->count() }} {{ __('lessons.show.reading') }}
                    </span>
                    @if ($totalFlashcards > 0)
                        <span class="rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-300">
                            {{ $totalFlashcards }} {{ __('lessons.show.flashcards') }}
                        </span>
                    @endif
                    @if ($quizQuestionCount > 0)
                        <span class="rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-300">
                            {{ $quizQuestionCount }} {{ __('lessons.show.mini_quiz') }}
                        </span>
                    @endif
                </div>

                <div class="mt-4 rounded-[1.25rem] border border-white/10 bg-stone-950/55 p-4 backdrop-blur">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-500">Objectif</p>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-amber-100">Débloquer l’entraînement</p>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">{{ __('lessons.show.progress') }}</p>
                        <p class="text-sm font-bold tabular-nums text-stone-100">{{ $progressPercent }}%</p>
                    </div>
                    <x-lessons.progress-bar :value="$progressPercent" class="mt-3" />
                    <a href="{{ $pathAnchors[$pathCurrentKey] }}"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-amber-200 py-3.5 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100 active:scale-[0.98]">
                        {{ $currentActionLabel }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        @if ($tabs->count() > 1)
            <nav class="sticky top-0 z-20 hidden sm:block">
                <div class="rs-panel flex gap-1.5 overflow-x-auto rounded-full px-2 py-2">
                    @foreach ($tabs as $tab)
                        <a href="{{ $tab['href'] }}"
                            class="shrink-0 rounded-full border border-transparent px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] transition
                                {{ $tab['key'] === $pathCurrentKey ? 'bg-amber-200 text-stone-950' : 'text-stone-400 hover:border-white/10 hover:text-stone-100' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        @if ($errors->any())
            <div class="rounded-[1.2rem] border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('lesson_quiz_result') === 'passed')
            <section class="rounded-[1.6rem] border border-emerald-300/25 bg-emerald-400/10 px-5 py-4">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-50">{{ __('lessons.show.quiz_passed') }}</p>
                <p class="mt-2 text-sm leading-6 text-stone-300">{{ __('lessons.show.training_unlocked') }}</p>
                <a href="{{ $trainingUrl }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-200 px-5 py-2.5 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                    {{ __('lessons.show.start_training') }}
                </a>
            </section>
        @elseif (session('lesson_quiz_result') === 'failed')
            <section class="rounded-[1.6rem] border border-amber-300/25 bg-amber-400/10 px-5 py-4">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-50">{{ __('lessons.show.quiz_failed') }}</p>
                <p class="mt-2 text-sm leading-6 text-stone-300">{{ __('lessons.show.quiz_failed_body') }}</p>
            </section>
        @endif

        @if ($lesson->sections->isNotEmpty())
            <section id="lesson-content" class="rs-panel rounded-[1.6rem] p-4 sm:rounded-[1.9rem] sm:p-6">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-sky-200/20 bg-sky-300/10 text-sky-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5A2.25 2.25 0 0 1 17.25 19.5H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h4.5" />
                        </svg>
                    </span>
                    <p class="rs-label">{{ __('lessons.show.reading') }}</p>
                </div>
                <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ __('lessons.show.start_reading') }}</h2>
                <p class="mt-2 text-sm leading-6 text-stone-400">Grimoire de préparation avant le deck et l’épreuve.</p>

                <div class="mt-4 space-y-3">
                    @foreach ($lesson->sections as $section)
                        <details class="overflow-hidden rounded-[1.2rem] border border-white/8 bg-white/5" {{ $loop->first ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4">
                                <div class="min-w-0">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] {{ $sectionTone[$section->type->value] ?? $sectionTone['summary'] }}">
                                        {{ __("lessons.show.sections.{$section->type->value}") }}
                                    </span>
                                    <h3 class="mt-3 text-base font-semibold leading-6 text-stone-50 sm:text-xl">{{ $section->title }}</h3>
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/8 bg-white/5 text-stone-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </span>
                            </summary>

                            <div class="border-t border-white/8 px-4 pb-4 pt-3">
                                <p class="whitespace-pre-line text-sm leading-7 text-stone-300">{{ $section->body }}</p>
                                @if ($section->image)
                                    <img src="{{ asset($section->image) }}" alt=""
                                        class="mt-4 aspect-video w-full rounded-[1rem] object-cover opacity-80">
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>

                @if ($totalFlashcards > 0)
                    <a href="#flashcards"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-full border border-white/10 py-3 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                        {{ __('lessons.show.continue_to_flashcards') }}
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                        </svg>
                    </a>
                @endif
            </section>
        @endif

        @if ($totalFlashcards > 0)
            <section id="flashcards" class="rs-panel rounded-[1.6rem] p-4 sm:rounded-[1.9rem] sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-200/20 bg-amber-200/10 text-amber-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h9A2.25 2.25 0 0 1 19.5 9v8.25A2.25 2.25 0 0 1 17.25 19.5h-9A2.25 2.25 0 0 1 6 17.25V9A2.25 2.25 0 0 1 8.25 6.75Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15V6.75A2.25 2.25 0 0 1 6.75 4.5H15" />
                                </svg>
                            </span>
                            <p class="rs-label">{{ __('lessons.show.flashcards') }}</p>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-stone-400">{{ __('lessons.show.flashcards_body') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-xs font-bold tabular-nums text-amber-100">
                        {{ $flashcardProgressPercent }}%
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-[1rem] border border-white/8 bg-white/5 px-2 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-stone-500">Vues</p>
                        <p class="mt-1 text-sm font-semibold text-stone-50">{{ $viewedFlashcards }}/{{ $totalFlashcards }}</p>
                    </div>
                    <div class="rounded-[1rem] border border-white/8 bg-white/5 px-2 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-stone-500">OK</p>
                        <p class="mt-1 text-sm font-semibold text-stone-50">{{ $masteredFlashcards }}</p>
                    </div>
                    <div class="rounded-[1rem] border border-white/8 bg-white/5 px-2 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-stone-500">À revoir</p>
                        <p class="mt-1 text-sm font-semibold text-stone-50">{{ $remainingFlashcards }}</p>
                    </div>
                </div>
                <x-lessons.progress-bar :value="$flashcardProgressPercent" class="mt-4" />

                <div class="mt-5" data-flashcard-deck>
                    @foreach ($lesson->flashcards as $flashcard)
                        @php
                            $nextCardIndex = min($loop->index + 1, max($totalFlashcards - 1, 0));
                        @endphp
                        <article data-flashcard-slide="{{ $loop->index }}" class="{{ $loop->first ? '' : 'hidden' }}">
                            <div class="flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">
                                <span>Rune {{ $flashcard->getAttribute('deck_index') }}/{{ $totalFlashcards }}</span>
                                <span data-flashcard-toggle-label class="text-amber-100/80">{{ __('lessons.show.flip_to_back') }}</span>
                            </div>

                            <div
                                data-flashcard-toggle
                                data-flip-label-front="{{ __('lessons.show.flip_to_back') }}"
                                data-flip-label-back="{{ __('lessons.show.flip_to_front') }}"
                                role="button"
                                tabindex="0"
                                class="group relative mt-3 min-h-[15rem] cursor-pointer overflow-hidden rounded-[1.3rem] border border-white/10 bg-gradient-to-br from-stone-950 via-stone-900 to-stone-950 p-4 outline-none transition hover:border-amber-200/30 focus-visible:border-amber-200/40 sm:min-h-[20rem] sm:rounded-[1.5rem] sm:p-6"
                            >
                                @if ($flashcard->image)
                                    <img src="{{ asset($flashcard->image) }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-15">
                                @endif

                                <div data-flashcard-face="front" class="relative flex h-full min-h-[13rem] flex-col justify-between gap-4 sm:min-h-[18rem]">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-100">{{ $flashcard->visual_label ?: __('lessons.show.flashcard_front') }}</p>
                                    <h3 class="text-xl font-semibold leading-tight text-stone-50 sm:text-3xl">{{ $flashcard->front }}</h3>
                                </div>

                                <div data-flashcard-face="back" class="relative hidden h-full min-h-[13rem] flex-col justify-between gap-4 sm:min-h-[18rem]">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-100">{{ __('lessons.show.flashcard_back') }}</p>
                                    <p class="text-base leading-7 text-stone-100 sm:text-xl">{{ $flashcard->back }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <button type="button" data-flashcard-prev
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 text-stone-400 transition hover:border-white/20 hover:text-stone-100"
                                    aria-label="{{ __('lessons.show.previous') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">{{ __('lessons.show.flip_to_back') }}</p>

                                <button type="button" data-flashcard-next
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 text-stone-400 transition hover:border-white/20 hover:text-stone-100"
                                    aria-label="{{ __('lessons.show.next') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <form method="POST" action="{{ route('lessons.flashcards.update', [$lesson, $flashcard]) }}">
                                    @csrf
                                    <input type="hidden" name="mastered" value="1">
                                    <input type="hidden" name="card" value="{{ $nextCardIndex }}">
                                    <button type="submit"
                                        class="w-full rounded-full bg-amber-200 px-3 py-3 text-xs font-bold uppercase tracking-[0.14em] text-stone-950 transition hover:bg-amber-100">
                                        {{ __('lessons.show.card_mastered') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('lessons.flashcards.update', [$lesson, $flashcard]) }}">
                                    @csrf
                                    <input type="hidden" name="mastered" value="0">
                                    <input type="hidden" name="card" value="{{ $nextCardIndex }}">
                                    <button type="submit"
                                        class="w-full rounded-full border border-white/10 px-3 py-3 text-xs font-bold uppercase tracking-[0.14em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                                        À revoir
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($quizQuestionCount > 0)
                    <a href="#mini-quiz"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-full border border-white/10 py-3 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                        {{ __('lessons.show.continue_to_quiz') }}
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                        </svg>
                    </a>
                @endif
            </section>
        @endif

        @if ($quizQuestionCount > 0)
            <section id="mini-quiz" class="rs-panel rounded-[1.6rem] p-4 sm:rounded-[1.9rem] sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200/20 bg-emerald-300/10 text-emerald-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </span>
                            <p class="rs-label">{{ __('lessons.show.mini_quiz') }}</p>
                        </div>
                        <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ $quizQuestionCount }}&nbsp;questions</h2>
                    </div>
                    <span class="shrink-0 rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-xs font-bold text-amber-100">
                        Objectif {{ $quizPassTarget }}/{{ $quizQuestionCount }}
                    </span>
                </div>
                <p class="mt-2 text-sm leading-6 text-stone-400">Épreuve finale de la leçon. {{ __('lessons.show.mini_quiz_body') }}</p>

                <form method="POST" action="{{ route('lessons.quiz', $lesson) }}" class="mt-4 grid gap-3">
                    @csrf

                    @foreach ($lesson->questions as $question)
                        @php
                            $promptMedia = $question->getAttribute('ui_prompt_media');
                            $answerLayout = $question->getAttribute('ui_answer_layout') ?? 'stack';
                        @endphp
                        <fieldset class="rounded-[1.2rem] border border-white/8 bg-stone-950/40 p-4 sm:rounded-[1.4rem] sm:p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-stone-400">
                                    Épreuve {{ $loop->iteration }}/{{ $quizQuestionCount }}
                                </span>
                                <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-amber-100">
                                    {{ $question->getAttribute('ui_mode_label') }}
                                </span>
                                @if ($question->getAttribute('ui_has_traps'))
                                    <span class="rounded-full border border-rose-300/20 bg-rose-300/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-rose-100">
                                        Pièges
                                    </span>
                                @endif
                            </div>

                            <legend class="mt-3 text-base font-semibold leading-6 text-stone-50 sm:text-lg sm:leading-7">{{ $question->question_text }}</legend>

                            @if ($question->getAttribute('ui_context'))
                                <p class="mt-2 text-sm leading-6 text-stone-400">{{ $question->getAttribute('ui_context') }}</p>
                            @endif

                            @if ($promptMedia)
                                <div class="mt-4 overflow-hidden rounded-[1rem] border border-white/10 bg-stone-950/35 sm:rounded-[1.2rem]">
                                    @if (! empty($promptMedia['image']))
                                        <img src="{{ asset($promptMedia['image']) }}" alt="" class="aspect-[5/3] w-full object-cover">
                                    @elseif (($promptMedia['kind'] ?? 'tile') === 'token')
                                        <div class="p-4">
                                            <div class="mx-auto flex max-w-md items-center gap-3 rounded-[1rem] border border-white/10 bg-white/5 px-4 py-3 text-left sm:rounded-[1.2rem]">
                                                <div class="flex h-12 min-w-12 items-center justify-center rounded-[0.85rem] border border-amber-200/20 bg-amber-200/10 px-3 text-lg font-semibold text-stone-50 sm:h-14 sm:min-w-14 sm:text-xl">
                                                    {{ $promptMedia['value'] }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-400">{{ $promptMedia['title'] }}</p>
                                                    @if (! empty($promptMedia['subtitle']))
                                                        <p class="mt-1.5 text-sm text-stone-300">{{ $promptMedia['subtitle'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex aspect-[5/3] items-center justify-center bg-gradient-to-br from-stone-950 to-stone-900 p-5 text-center">
                                            <div>
                                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-400">{{ $promptMedia['title'] }}</p>
                                                <p class="mt-3 text-3xl font-semibold text-stone-50 sm:text-4xl">{{ $promptMedia['value'] }}</p>
                                                @if (! empty($promptMedia['subtitle']))
                                                    <p class="mt-2 text-sm text-stone-400">{{ $promptMedia['subtitle'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4 grid gap-2 {{ $answerLayout === 'visual-grid' ? 'sm:grid-cols-2' : ($answerLayout === 'compact-grid' ? 'sm:grid-cols-2 lg:grid-cols-4' : '') }}">
                                @foreach ($question->answers as $answer)
                                    <label class="block cursor-pointer">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $answer->id }}" required class="peer sr-only" @checked((int) old("answers.{$question->id}") === $answer->id)>
                                        <span class="rs-choice flex h-full flex-col justify-between">
                                            @if ($answer->getAttribute('ui_image'))
                                                <img src="{{ asset($answer->getAttribute('ui_image')) }}" alt="" class="aspect-[4/3] w-full rounded-[1rem] object-cover">
                                            @endif
                                            <div class="@if ($answer->getAttribute('ui_image')) mt-3 @endif">
                                                @if ($answer->getAttribute('ui_badge'))
                                                    <span class="inline-flex rounded-full border border-white/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-stone-300">
                                                        {{ $answer->getAttribute('ui_badge') }}
                                                    </span>
                                                @endif
                                                <span class="@if ($answer->getAttribute('ui_badge')) mt-2 @endif block text-sm font-semibold leading-6 text-stone-50 sm:text-base">{{ $answer->answer_text }}</span>
                                                @if ($answer->getAttribute('ui_note'))
                                                    <span class="mt-1.5 block text-xs leading-5 text-stone-400">{{ $answer->getAttribute('ui_note') }}</span>
                                                @endif
                                            </div>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <button type="submit"
                        class="mt-1 w-full rounded-full bg-amber-200 py-3.5 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100 active:scale-[0.98]">
                        {{ __('lessons.show.submit_quiz') }}
                    </button>
                </form>
            </section>
        @endif

        @if ($progress->quiz_passed)
            <section class="relative overflow-hidden rounded-[1.9rem] border border-emerald-200/15 bg-emerald-300/8 p-5 sm:p-6">
                <p class="rs-label text-emerald-200">{{ __('lessons.show.summary_title') }}</p>
                <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ __('lessons.show.quiz_passed') }}</h2>
                <p class="mt-2 text-sm leading-6 text-stone-300">{{ __('lessons.show.training_unlocked') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-amber-300/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-50">
                        {{ __('lessons.show.academic_xp', ['xp' => $progress->academic_xp_awarded]) }}
                    </span>
                </div>
                <a href="{{ $trainingUrl }}"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-amber-200 py-3.5 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100">
                    {{ __('lessons.show.start_training') }}
                </a>
            </section>
        @endif
    </div>

    @if ($lesson->flashcards->isNotEmpty())
        <script>
            (() => {
                const deck = document.querySelector('[data-flashcard-deck]');
                if (!deck) return;

                const slides = Array.from(deck.querySelectorAll('[data-flashcard-slide]'));
                let activeIndex = 0;

                const setSlideFlipped = (slide, flipped) => {
                    if (!slide) return;
                    const front  = slide.querySelector('[data-flashcard-face="front"]');
                    const back   = slide.querySelector('[data-flashcard-face="back"]');
                    const labels = slide.querySelectorAll('[data-flashcard-toggle-label]');
                    const card   = slide.querySelector('[data-flashcard-toggle]');
                    const fLabel = card?.dataset.flipLabelFront ?? '';
                    const bLabel = card?.dataset.flipLabelBack ?? '';

                    slide.dataset.flipped = flipped ? 'true' : 'false';
                    front?.classList.toggle('hidden', flipped);
                    back?.classList.toggle('hidden', !flipped);
                    labels.forEach((label) => { label.textContent = flipped ? bLabel : fLabel; });
                };

                const updateUrl = (index) => {
                    const url = new URL(window.location.href);
                    if (index > 0) url.searchParams.set('card', index);
                    else url.searchParams.delete('card');
                    url.hash = 'flashcards';
                    window.history.replaceState({}, '', url);
                };

                const showSlide = (index) => {
                    activeIndex = (index + slides.length) % slides.length;
                    slides.forEach((slide, i) => slide.classList.toggle('hidden', i !== activeIndex));
                    setSlideFlipped(slides[activeIndex], false);
                    updateUrl(activeIndex);
                };

                deck.addEventListener('click', (event) => {
                    const slide = event.target.closest('[data-flashcard-slide]');
                    const toggle = event.target.closest('[data-flashcard-toggle]');
                    const prev = event.target.closest('[data-flashcard-prev]');
                    const next = event.target.closest('[data-flashcard-next]');

                    if (toggle && slide) setSlideFlipped(slide, slide.dataset.flipped !== 'true');
                    if (prev) showSlide(activeIndex - 1);
                    if (next) showSlide(activeIndex + 1);
                });

                deck.addEventListener('keydown', (event) => {
                    const toggle = event.target.closest('[data-flashcard-toggle]');
                    const slide = event.target.closest('[data-flashcard-slide]');
                    if (!toggle || !slide || !['Enter', ' '].includes(event.key)) return;
                    event.preventDefault();
                    setSlideFlipped(slide, slide.dataset.flipped !== 'true');
                });

                slides.forEach((slide) => setSlideFlipped(slide, false));

                const requestedIndex = Number(new URLSearchParams(window.location.search).get('card') ?? 0);
                showSlide(Number.isFinite(requestedIndex) ? requestedIndex : 0);
            })();
        </script>
    @endif
</x-layouts::app>
