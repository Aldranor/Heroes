<x-layouts::app :title="__('training.page_title')">
    @php
        $retryRoute = $trainingSession->adventureNode
            ? route('missions.index', ['world' => $trainingSession->adventureNode->map?->world_id, 'map' => $trainingSession->adventureNode->map?->id, 'node' => $trainingSession->adventure_node_id])
            : route('training.create', ['learning_domain_id' => $trainingSession->learning_domain_id, 'learning_category_id' => $trainingSession->learning_category_id]);
    @endphp
    <div class="flex h-full w-full flex-1 flex-col gap-5">
        <section class="rs-panel rounded-[2rem] p-5 sm:p-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="rs-label">{{ __('training.result.eyebrow') }}</p>
                    <h1 class="rs-display mt-2 text-4xl leading-none text-stone-50 sm:text-5xl">{{ __('training.result.title') }}</h1>
                    <p class="mt-3 text-sm leading-7 text-stone-300">
                        Mission complétée · {{ data_get($trainingSession->metadata, 'mission.node_title', $trainingSession->learningCategory->name) }} · {{ $trainingSession->userCompanion->companion->name }}
                    </p>
                </div>

                @if ($trainingSession->perfect_session)
                    <span class="rounded-full border border-emerald-200/20 bg-emerald-300/10 px-4 py-2 text-sm font-bold uppercase tracking-[0.18em] text-emerald-50">
                        {{ __('training.result.perfect') }}
                    </span>
                @endif
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('training.result.score') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">{{ $trainingSession->correct_answers }}/{{ $trainingSession->total_questions }}</p>
                </div>
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('training.result.xp') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">+{{ $trainingSession->xp_earned }}</p>
                </div>
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('training.result.coins') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">+{{ $trainingSession->coins_earned }}</p>
                </div>
            </div>
        </section>

        <section class="rs-panel rounded-[1.9rem] p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="rs-label">{{ __('training.result.review') }}</p>
                    <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ data_get($trainingSession->metadata, 'mission.node_title', $trainingSession->learningCategory->name) }}</h2>
                </div>
                <div class="flex gap-2">
                    <a href="{{ $retryRoute }}" class="rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950">
                        {{ __('training.result.again') }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100">
                        {{ __('training.result.hub') }}
                    </a>
                </div>
            </div>

            <div class="mt-5 grid gap-3">
                @foreach ($reviewAnswers as $reviewAnswer)
                    <article class="rounded-[1.4rem] border {{ $reviewAnswer->is_correct ? 'border-emerald-300/20 bg-emerald-300/8' : 'border-rose-300/20 bg-rose-300/8' }} px-4 py-4">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-stone-300">
                                            {{ $reviewAnswer->question->getAttribute('ui_mode_label') }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] {{ $reviewAnswer->is_correct ? 'bg-emerald-300/15 text-emerald-50' : 'bg-rose-300/15 text-rose-50' }}">
                                            {{ $reviewAnswer->is_correct ? __('training.feedback.correct') : __('training.feedback.incorrect') }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold leading-6 text-stone-100">{{ $reviewAnswer->question->question_text }}</p>
                                    <p class="mt-2 text-sm text-stone-400">
                                        {{ $reviewAnswer->answer->answer_text }}
                                    </p>
                                    @if (! $reviewAnswer->is_correct && $reviewAnswer->question->correctAnswer)
                                        <p class="mt-2 text-sm text-emerald-100">
                                            {{ __('training.feedback.correct_answer') }}: {{ $reviewAnswer->question->correctAnswer->answer_text }}
                                        </p>
                                    @endif
                                    @if ($reviewAnswer->question->explanation)
                                        <p class="mt-2 text-sm leading-6 text-stone-300">{{ $reviewAnswer->question->explanation }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if ($reviewAnswer->getAttribute('revision')['flashcards_url'])
                                    <a href="{{ $reviewAnswer->getAttribute('revision')['flashcards_url'] }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                                        Flashcards
                                    </a>
                                @endif
                                @if ($reviewAnswer->getAttribute('revision')['lesson_url'])
                                    <a href="{{ $reviewAnswer->getAttribute('revision')['lesson_url'] }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                                        Cours
                                    </a>
                                @endif
                                <a href="{{ $reviewAnswer->getAttribute('revision')['training_url'] }}" class="rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                                    Révision ciblée
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts::app>
