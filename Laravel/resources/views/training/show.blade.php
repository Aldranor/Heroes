<x-layouts::app :title="__('training.page_title')">
    @php
        $skills = array_merge([
            'hint' => 0,
            'extra_time' => 0,
            'remove_choice' => 0,
            'bonus_xp' => 0,
        ], $trainingSession->metadata['skills'] ?? []);
        $timerSeconds = $question?->getAttribute('ui_timer_seconds') ?? 0;
        $promptMedia = $question?->getAttribute('ui_prompt_media');
        $answerLayout = $question?->getAttribute('ui_answer_layout') ?? 'stack';
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-5">
        <section class="rs-panel rounded-[2rem] p-5 sm:p-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="rs-label">{{ __('training.quiz.eyebrow') }}</p>
                    <h1 class="rs-display mt-2 text-4xl leading-none text-stone-50 sm:text-5xl">
                        {{ data_get($trainingSession->metadata, 'mission.node_title', $trainingSession->learningCategory->name) }}
                    </h1>
                    <p class="mt-3 text-sm leading-7 text-stone-300">
                        {{ $trainingSession->learningDomain->name }} · {{ $trainingSession->userCompanion->companion->name }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-stone-100">
                        {{ __('training.quiz.question_count', ['current' => min($answeredCount + 1, $trainingSession->total_questions), 'total' => $trainingSession->total_questions]) }}
                    </span>
                    @if ($question)
                        <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-4 py-2 text-sm font-semibold text-amber-50">
                            {{ $question->getAttribute('ui_mode_label') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/8">
                <div class="h-full rounded-full bg-amber-200" style="width: {{ (int) (($answeredCount / $trainingSession->total_questions) * 100) }}%"></div>
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-[1.2rem] border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($feedback)
            <section class="rounded-[1.6rem] border {{ $feedback['answer']->is_correct ? 'border-emerald-300/30 bg-emerald-400/10' : 'border-rose-300/30 bg-rose-400/10' }} px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] {{ $feedback['answer']->is_correct ? 'text-emerald-100' : 'text-rose-100' }}">
                            {{ $feedback['answer']->is_correct ? __('training.feedback.correct') : __('training.feedback.incorrect') }}
                        </p>
                        @if (! $feedback['answer']->is_correct && $feedback['correctAnswer'])
                            <p class="mt-2 text-sm text-stone-200">
                                {{ __('training.feedback.correct_answer') }}: {{ $feedback['correctAnswer']->answer_text }}
                            </p>
                        @endif
                        @if ($feedback['explanation'])
                            <p class="mt-2 text-sm leading-6 text-stone-300">{{ $feedback['explanation'] }}</p>
                        @endif
                    </div>

                    <div class="grid gap-2 text-sm font-semibold text-stone-100">
                        <span>{{ __('training.feedback.user_xp', ['xp' => $feedback['answer']->awarded_user_xp]) }}</span>
                        <span>{{ __('training.feedback.companion_xp', ['xp' => $feedback['answer']->awarded_companion_xp]) }}</span>
                    </div>
                </div>

                @if ($feedback['revision'])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($feedback['revision']['flashcards_url'])
                            <a href="{{ $feedback['revision']['flashcards_url'] }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                                Réviser les flashcards
                            </a>
                        @endif
                        @if ($feedback['revision']['lesson_url'])
                            <a href="{{ $feedback['revision']['lesson_url'] }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                                Revoir le cours
                            </a>
                        @endif
                        <a href="{{ $feedback['revision']['training_url'] }}" class="rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                            Session ciblée
                        </a>
                    </div>
                @endif
            </section>
        @endif

        <section class="rs-panel rounded-[1.9rem] p-5 sm:p-6">
            @if ($question)
                <div class="grid gap-5 xl:grid-cols-[1fr_20rem]">
                    <div class="space-y-5">
                        <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                            <div class="rounded-[1.6rem] border border-white/10 bg-stone-950/42 p-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-100">
                                        {{ $question->getAttribute('ui_mode_label') }}
                                    </span>
                                    @if ($question->getAttribute('ui_has_traps'))
                                        <span class="rounded-full border border-rose-300/20 bg-rose-300/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-rose-100">
                                            Pièges
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-4 text-sm leading-6 text-stone-400">{{ $question->learningCategory->name }}</p>
                                <h2 class="mt-3 text-3xl font-semibold leading-tight text-stone-50">{{ $question->question_text }}</h2>

                                @if ($question->getAttribute('ui_context'))
                                    <p class="mt-3 rounded-[1rem] border border-white/8 bg-white/5 px-4 py-3 text-sm leading-6 text-stone-300">
                                        {{ $question->getAttribute('ui_context') }}
                                    </p>
                                @endif
                            </div>

                            <div class="rounded-[1.6rem] border border-amber-200/18 bg-amber-200/10 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="rs-label">Timer</p>
                                        <p data-training-timer class="mt-2 text-3xl font-semibold text-stone-50">{{ str_pad((string) $timerSeconds, 2, '0', STR_PAD_LEFT) }}s</p>
                                    </div>
                                    <div class="rounded-full border border-white/10 bg-stone-950/35 px-3 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100">
                                        Skills
                                    </div>
                                </div>

                                @if ($promptMedia)
                                    <div class="mt-5 overflow-hidden rounded-[1.35rem] border border-white/10 bg-stone-950/40">
                                        @if (! empty($promptMedia['image']))
                                            <img src="{{ asset($promptMedia['image']) }}" alt="" class="aspect-[5/4] w-full object-cover">
                                        @elseif (($promptMedia['kind'] ?? 'tile') === 'token')
                                            <div class="p-5">
                                                <div class="flex items-center gap-4 rounded-[1.25rem] border border-white/10 bg-white/5 px-5 py-4 text-left">
                                                    <div class="flex h-14 min-w-14 items-center justify-center rounded-[1rem] border border-amber-200/20 bg-amber-200/10 px-4 text-xl font-semibold text-stone-50">
                                                        {{ $promptMedia['value'] }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-400">{{ $promptMedia['title'] }}</p>
                                                        @if (! empty($promptMedia['subtitle']))
                                                            <p class="mt-2 text-sm text-stone-300">{{ $promptMedia['subtitle'] }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex aspect-[5/4] items-center justify-center bg-gradient-to-br from-stone-950 to-stone-900 p-5 text-center" style="border-color: {{ $promptMedia['accent'] ?? '#E0A458' }}33;">
                                                <div>
                                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-400">{{ $promptMedia['title'] }}</p>
                                                    <p class="mt-4 text-4xl font-semibold text-stone-50">{{ $promptMedia['value'] }}</p>
                                                    @if (! empty($promptMedia['subtitle']))
                                                        <p class="mt-3 text-sm text-stone-400">{{ $promptMedia['subtitle'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('training.answer', $trainingSession) }}" class="grid gap-4" data-training-form data-base-timer="{{ $timerSeconds }}">
                            @csrf
                            <input type="hidden" name="time_spent_seconds" value="0" data-training-time-field>
                            <input type="hidden" name="use_hint" value="0" data-training-skill-field="hint">
                            <input type="hidden" name="use_extra_time" value="0" data-training-skill-field="extra_time">
                            <input type="hidden" name="use_remove_choice" value="0" data-training-skill-field="remove_choice">
                            <input type="hidden" name="use_bonus_xp" value="0" data-training-skill-field="bonus_xp">

                            <div class="grid gap-3 rounded-[1.5rem] border border-white/10 bg-stone-950/32 p-4 md:grid-cols-2 xl:grid-cols-4">
                                <button type="button" data-training-skill="hint" data-disabled="{{ $skills['hint'] <= 0 || ! $question->getAttribute('ui_hint') ? '1' : '0' }}" class="rounded-[1.1rem] border border-white/10 bg-white/5 px-4 py-3 text-left text-sm font-semibold text-stone-100 transition hover:border-amber-200/30 disabled:cursor-not-allowed disabled:opacity-40">
                                    Indice
                                    <span class="mt-1 block text-xs font-normal text-stone-400">{{ $skills['hint'] }} charge</span>
                                </button>
                                <button type="button" data-training-skill="extra_time" data-disabled="{{ $skills['extra_time'] <= 0 ? '1' : '0' }}" class="rounded-[1.1rem] border border-white/10 bg-white/5 px-4 py-3 text-left text-sm font-semibold text-stone-100 transition hover:border-amber-200/30 disabled:cursor-not-allowed disabled:opacity-40">
                                    Temps +
                                    <span class="mt-1 block text-xs font-normal text-stone-400">{{ $skills['extra_time'] }} charge</span>
                                </button>
                                <button type="button" data-training-skill="remove_choice" data-disabled="{{ $skills['remove_choice'] <= 0 ? '1' : '0' }}" class="rounded-[1.1rem] border border-white/10 bg-white/5 px-4 py-3 text-left text-sm font-semibold text-stone-100 transition hover:border-amber-200/30 disabled:cursor-not-allowed disabled:opacity-40">
                                    Supprimer réponse
                                    <span class="mt-1 block text-xs font-normal text-stone-400">{{ $skills['remove_choice'] }} charge</span>
                                </button>
                                <button type="button" data-training-skill="bonus_xp" data-disabled="{{ $skills['bonus_xp'] <= 0 ? '1' : '0' }}" class="rounded-[1.1rem] border border-white/10 bg-white/5 px-4 py-3 text-left text-sm font-semibold text-stone-100 transition hover:border-amber-200/30 disabled:cursor-not-allowed disabled:opacity-40">
                                    Bonus XP
                                    <span class="mt-1 block text-xs font-normal text-stone-400">{{ $skills['bonus_xp'] }} charge</span>
                                </button>
                            </div>

                            <div data-training-hint class="hidden rounded-[1.3rem] border border-sky-300/20 bg-sky-300/10 px-4 py-4 text-sm leading-6 text-sky-50">
                                {{ $question->getAttribute('ui_hint') }}
                            </div>

                            <div class="grid gap-3 {{ $answerLayout === 'visual-grid' ? 'md:grid-cols-2' : ($answerLayout === 'compact-grid' ? 'md:grid-cols-2 xl:grid-cols-4' : '') }}">
                                @foreach ($question->answers as $answer)
                                    <button type="submit" name="answer_id" value="{{ $answer->id }}" data-answer-option data-is-correct="{{ $answer->is_correct ? '1' : '0' }}" class="rounded-[1.35rem] border border-white/10 bg-white/5 p-4 text-left text-sm font-semibold leading-6 text-stone-100 transition hover:border-amber-200/40 hover:bg-amber-200/10">
                                        @if ($answer->getAttribute('ui_image'))
                                            <img src="{{ asset($answer->getAttribute('ui_image')) }}" alt="" class="aspect-[4/3] w-full rounded-[1rem] object-cover">
                                        @endif
                                        @if ($answer->getAttribute('ui_badge'))
                                            <span class="mt-3 inline-flex rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-300">{{ $answer->getAttribute('ui_badge') }}</span>
                                        @endif
                                        <span class="mt-3 block text-base leading-7 text-stone-50">{{ $answer->answer_text }}</span>
                                        @if ($answer->getAttribute('ui_note'))
                                            <span class="mt-2 block text-xs leading-5 text-stone-400">{{ $answer->getAttribute('ui_note') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </div>

                    <aside class="rounded-[1.6rem] border border-white/10 bg-stone-950/32 p-4">
                        <p class="rs-label">Rappels</p>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-3 py-3">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Feedback</p>
                                <p class="mt-2 text-sm font-semibold text-stone-50">Correction immédiate</p>
                            </div>
                            <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-3 py-3">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Progression</p>
                                <p class="mt-2 text-sm font-semibold text-stone-50">XP héros, compagnon et maîtrise</p>
                            </div>
                            <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-3 py-3">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Révision</p>
                                <p class="mt-2 text-sm font-semibold text-stone-50">Flashcards, cours, session ciblée</p>
                            </div>
                        </div>
                    </aside>
                </div>
            @elseif ($readyForResult)
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="rs-label">{{ __('training.quiz.complete_title') }}</p>
                        <p class="mt-2 text-sm leading-6 text-stone-300">{{ __('training.quiz.complete_body') }}</p>
                    </div>
                    <a href="{{ route('training.result', $trainingSession) }}" class="inline-flex justify-center rounded-full bg-amber-200 px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100">
                        {{ __('training.quiz.result') }}
                    </a>
                </div>
            @endif
        </section>
    </div>

    @if ($question)
        <script>
            (() => {
                const form = document.querySelector('[data-training-form]');
                if (!form) {
                    return;
                }

                const timeField = form.querySelector('[data-training-time-field]');
                const timerNode = document.querySelector('[data-training-timer]');
                const hintPanel = form.querySelector('[data-training-hint]');
                const baseTimer = Number(form.dataset.baseTimer || 0);
                let remaining = baseTimer;
                let elapsed = 0;

                const syncTime = () => {
                    if (timeField) {
                        timeField.value = String(elapsed);
                    }

                    if (timerNode) {
                        timerNode.textContent = `${String(Math.max(remaining, 0)).padStart(2, '0')}s`;
                        timerNode.classList.toggle('text-rose-100', remaining <= 5);
                    }
                };

                const interval = window.setInterval(() => {
                    elapsed += 1;
                    remaining -= 1;
                    syncTime();

                    if (remaining <= 0) {
                        window.clearInterval(interval);
                    }
                }, 1000);

                syncTime();

                const setSkill = (skill, enabled) => {
                    const field = form.querySelector(`[data-training-skill-field="${skill}"]`);
                    if (field) {
                        field.value = enabled ? '1' : '0';
                    }
                };

                form.querySelectorAll('[data-training-skill]').forEach((button) => {
                    if (button.dataset.disabled === '1') {
                        button.disabled = true;
                    }

                    button.addEventListener('click', () => {
                        if (button.disabled) {
                            return;
                        }

                        const skill = button.dataset.trainingSkill;

                        if (skill === 'hint') {
                            hintPanel?.classList.remove('hidden');
                            setSkill('hint', true);
                            button.disabled = true;
                            button.classList.add('border-sky-300/40', 'bg-sky-300/10');
                        }

                        if (skill === 'extra_time') {
                            remaining += 10;
                            setSkill('extra_time', true);
                            button.disabled = true;
                            button.classList.add('border-amber-200/40', 'bg-amber-200/10');
                            syncTime();
                        }

                        if (skill === 'remove_choice') {
                            const candidate = Array.from(form.querySelectorAll('[data-answer-option]'))
                                .find((option) => option.dataset.isCorrect === '0' && !option.hidden);

                            if (candidate) {
                                candidate.hidden = true;
                            }

                            setSkill('remove_choice', true);
                            button.disabled = true;
                            button.classList.add('border-rose-300/40', 'bg-rose-300/10');
                        }

                        if (skill === 'bonus_xp') {
                            const isActive = button.dataset.active === '1';
                            button.dataset.active = isActive ? '0' : '1';
                            setSkill('bonus_xp', !isActive);
                            button.classList.toggle('border-emerald-300/40', !isActive);
                            button.classList.toggle('bg-emerald-300/10', !isActive);
                        }
                    });
                });

                form.addEventListener('submit', () => {
                    syncTime();
                    window.clearInterval(interval);
                });
            })();
        </script>
    @endif
</x-layouts::app>
