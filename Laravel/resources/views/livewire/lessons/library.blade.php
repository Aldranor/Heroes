@php
    $journeySteps = ['lesson', 'flashcards', 'quiz', 'training'];
    $journeyStatusClasses = [
        'done' => 'border-emerald-200/25 bg-emerald-300/12 text-emerald-50',
        'current' => 'border-amber-200/30 bg-amber-200/12 text-amber-50',
        'upcoming' => 'border-white/8 bg-white/5 text-stone-300',
        'unlocked' => 'border-sky-200/25 bg-sky-300/12 text-sky-50',
    ];
@endphp

<div class="flex h-full w-full flex-1 flex-col gap-5">
    <section class="rs-panel rs-hero relative overflow-hidden rounded-[2rem] p-5 sm:p-7">
        <img src="{{ asset('images/backgrounds/cours.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35">
        <div class="absolute inset-0 bg-gradient-to-r from-stone-950/96 via-stone-950/84 to-stone-950/52"></div>
        <div class="relative grid gap-5 lg:grid-cols-[1fr_0.78fr] lg:items-end">
            <div>
                <p class="rs-label">{{ __('lessons.library.eyebrow') }}</p>
                <h1 class="rs-display mt-2 text-4xl leading-none text-stone-50 sm:text-5xl">{{ __('lessons.library.title') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-300 sm:text-base">
                    {{ __('lessons.library.body') }}
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('lessons.library.stats.modules') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">{{ $stats['lessons'] }}</p>
                </div>
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('lessons.library.stats.mastered') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">{{ $stats['mastered'] }}</p>
                </div>
                <div class="rs-stat-chip">
                    <p class="rs-label">{{ __('lessons.library.stats.progress') }}</p>
                    <p class="mt-2 text-lg font-semibold text-stone-50">{{ $selectedDomain?->getAttribute('library_progress_percent') ?? 0 }}%</p>
                </div>
            </div>
        </div>

        <div class="relative mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($journeySteps as $stepKey)
                <div class="rounded-[1.3rem] border border-white/8 bg-stone-950/42 p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-200/20 bg-amber-200/10 text-xs font-bold text-amber-50">
                            {{ $loop->iteration }}
                        </span>
                        <p class="text-sm font-semibold text-stone-50">
                            {{ __("lessons.library.journey.steps.{$stepKey}.title") }}
                        </p>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-stone-400">
                        {{ __("lessons.library.journey.steps.{$stepKey}.body") }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($domains->isEmpty())
        <section class="rs-panel rounded-[1.9rem] p-5 text-sm text-stone-300">
            {{ __('lessons.classroom.empty') }}
        </section>
    @else
        <div class="grid gap-5 xl:grid-cols-[18rem_1fr]">
            <aside class="rs-panel rounded-[1.8rem] p-4 xl:sticky xl:top-5 xl:self-start">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="rs-label">{{ __('lessons.library.domains') }}</p>
                        <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ __('lessons.library.academy_map') }}</h2>
                    </div>
                </div>

                <div class="mt-4 grid gap-2">
                    @foreach ($domains as $domain)
                        @php
                            $isSelectedDomain = $selectedDomain?->id === $domain->id;
                        @endphp
                        <button
                            type="button"
                            wire:click="selectDomain({{ $domain->id }})"
                            class="rounded-[1.2rem] border px-3 py-3 text-left transition {{ $isSelectedDomain ? 'border-amber-200/35 bg-amber-200/12' : 'border-white/8 bg-white/4 hover:border-white/16' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-stone-50">{{ $domain->name }}</p>
                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-stone-400">{{ $domain->description }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-bold text-amber-100">{{ $domain->getAttribute('library_progress_percent') }}%</span>
                            </div>
                            <x-lessons.progress-bar :value="$domain->getAttribute('library_progress_percent')" class="mt-3" />
                        </button>
                    @endforeach
                </div>
            </aside>

            <main class="grid gap-5">
                @if ($recommendation)
                    <section class="rs-panel relative overflow-hidden rounded-[1.9rem] p-5 sm:p-6">
                        <img src="{{ asset('images/backgrounds/training_center.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-20">
                        <div class="absolute inset-0 bg-gradient-to-r from-stone-950 via-stone-950/88 to-stone-950/68"></div>
                        <div class="relative grid gap-4 xl:grid-cols-[1fr_20rem] xl:items-start">
                            <div>
                                <p class="rs-label">{{ __('lessons.library.recommendation.eyebrow') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($recommendation['topic_title'])
                                        <span class="rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-200">
                                            {{ $recommendation['topic_title'] }}
                                        </span>
                                    @endif
                                    @if ($recommendation['domain_name'])
                                        <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-100">
                                            {{ $recommendation['domain_name'] }}
                                        </span>
                                    @endif
                                </div>
                                <h2 class="rs-display mt-3 text-3xl text-stone-50">{{ $recommendation['title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-stone-300">
                                    {{ __('lessons.library.recommendation.body', ['progress' => $recommendation['progress']]) }}
                                </p>
                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-400">
                                    {{ $recommendation['reason'] }}
                                </p>

                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-4 py-3">
                                        <p class="rs-label">{{ __('lessons.library.lesson_progress') }}</p>
                                        <p class="mt-2 text-lg font-semibold text-stone-50">{{ $recommendation['progress'] }}%</p>
                                    </div>
                                    <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-4 py-3">
                                        <p class="rs-label">{{ __('lessons.library.flashcards_progress') }}</p>
                                        <p class="mt-2 text-lg font-semibold text-stone-50">{{ $recommendation['flashcards_count'] }}</p>
                                    </div>
                                    <div class="rounded-[1.1rem] border border-white/8 bg-white/5 px-4 py-3">
                                        <p class="rs-label">{{ __('lessons.library.quiz_count') }}</p>
                                        <p class="mt-2 text-lg font-semibold text-stone-50">{{ $recommendation['questions_count'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <div class="rounded-[1.4rem] border border-amber-200/20 bg-amber-200/10 p-4">
                                    <p class="rs-label">{{ __('lessons.library.next_step') }}</p>
                                    <p class="mt-2 text-lg font-semibold text-stone-50">{{ $recommendation['next_step_title'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-stone-300">{{ $recommendation['next_step_body'] }}</p>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <x-lessons.action-link :href="$recommendation['next_action_url']">{{ $recommendation['next_action_label'] }}</x-lessons.action-link>
                                    @if ($recommendation['next_action_url'] !== $recommendation['lesson_url'])
                                        <x-lessons.action-link :href="$recommendation['lesson_url']" variant="secondary">{{ __('lessons.library.actions.open') }}</x-lessons.action-link>
                                    @endif
                                    @if ($recommendation['flashcards_count'] > 0 && $recommendation['next_action_url'] !== $recommendation['flashcards_url'])
                                        <x-lessons.action-link :href="$recommendation['flashcards_url']" variant="secondary">{{ __('lessons.library.actions.review') }}</x-lessons.action-link>
                                    @endif
                                    @if (! $recommendation['training_locked'] && $recommendation['next_action_url'] !== $recommendation['training_url'])
                                        <x-lessons.action-link :href="$recommendation['training_url']" variant="secondary">{{ __('lessons.library.actions.train') }}</x-lessons.action-link>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($selectedDomain)
                    <section class="grid gap-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="rs-label">{{ __('lessons.library.domain') }}</p>
                                <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ $selectedDomain->name }}</h2>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">{{ $selectedDomain->description }}</p>
                            </div>

                            <div class="w-full max-w-xs">
                                <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-[0.16em] text-stone-400">
                                    <span>{{ __('lessons.library.global_progress') }}</span>
                                    <span>{{ $selectedDomain->getAttribute('library_progress_percent') }}%</span>
                                </div>
                                <x-lessons.progress-bar :value="$selectedDomain->getAttribute('library_progress_percent')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                wire:click="selectTopic"
                                class="shrink-0 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] transition {{ $selectedTopic ? 'border border-white/10 text-stone-300 hover:border-white/20' : 'bg-amber-200 text-stone-950' }}"
                            >
                                {{ __('lessons.library.all_topics') }}
                            </button>

                            @foreach ($selectedDomain->topics as $topic)
                                <button
                                    type="button"
                                    wire:click="selectTopic({{ $topic->id }})"
                                    class="shrink-0 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] transition {{ $selectedTopic?->id === $topic->id ? 'bg-amber-200 text-stone-950' : 'border border-white/10 text-stone-300 hover:border-white/20' }}"
                                >
                                    {{ $topic->title }}
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="grid gap-5">
                    @foreach ($visibleTopics as $topic)
                        <article class="grid gap-3">
                            <div class="rs-panel rounded-[1.8rem] p-5 sm:p-6">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <p class="rs-label">{{ __('lessons.library.topic') }}</p>
                                        <h3 class="rs-display mt-2 text-3xl text-stone-50">{{ $topic->title }}</h3>
                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">{{ $topic->description }}</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-[12rem_auto] sm:items-end">
                                        <div>
                                            <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-[0.16em] text-stone-400">
                                                <span>{{ __('lessons.library.topic_progress') }}</span>
                                                <span>{{ $topic->getAttribute('library_progress_percent') }}%</span>
                                            </div>
                                            <x-lessons.progress-bar :value="$topic->getAttribute('library_progress_percent')" class="mt-2" />
                                            <p class="mt-2 text-xs text-stone-500">
                                                {{ __('lessons.library.topic_mastery', ['mastered' => $topic->getAttribute('library_mastered_count'), 'total' => $topic->getAttribute('library_lessons_count')]) }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="toggleMindmap({{ $topic->id }})"
                                            class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50"
                                        >
                                            {{ $mindmapTopicId === $topic->id ? __('lessons.library.mindmap.close') : __('lessons.library.mindmap.open') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if ($mindmapTopicId === $topic->id)
                                <section class="rounded-[1.8rem] border border-amber-200/16 bg-stone-950/56 p-5 shadow-2xl shadow-stone-950/30">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                        <div>
                                            <p class="rs-label">{{ __('lessons.library.mindmap.eyebrow') }}</p>
                                            <h4 class="rs-display mt-1 text-2xl text-stone-50">{{ __('lessons.library.mindmap.title', ['topic' => $topic->title]) }}</h4>
                                        </div>
                                        <span class="text-xs font-bold uppercase tracking-[0.16em] text-amber-100">{{ $topic->lessons->count() }} {{ __('lessons.library.mindmap.nodes') }}</span>
                                    </div>

                                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($topic->lessons as $lesson)
                                            <div class="rounded-[1.3rem] border border-white/8 bg-white/5 p-4">
                                                <div class="flex items-start justify-between gap-3">
                                                    <a href="{{ route('lessons.show', $lesson) }}" class="text-sm font-semibold leading-6 text-stone-50 hover:text-amber-100">
                                                        {{ $lesson->title }}
                                                    </a>
                                                    <span class="text-xs font-bold text-amber-100">{{ $lesson->getAttribute('library_progress_percent') }}%</span>
                                                </div>
                                                <x-lessons.progress-bar :value="$lesson->getAttribute('library_progress_percent')" class="mt-3" />
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <a href="{{ route('lessons.show', $lesson) }}" class="text-xs font-semibold text-stone-300 underline decoration-white/20 underline-offset-4 hover:text-stone-50">
                                                        {{ __('lessons.library.actions.open') }}
                                                    </a>
                                                    <a href="{{ route('lessons.show', $lesson) }}#flashcards" class="text-xs font-semibold text-stone-300 underline decoration-white/20 underline-offset-4 hover:text-stone-50">
                                                        {{ __('lessons.library.actions.review') }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            <div class="grid gap-3">
                                @foreach ($topic->lessons as $lesson)
                                    @php
                                        $progress = $lesson->getAttribute('library_progress_percent');
                                        $statusKey = $lesson->getAttribute('library_status_key');
                                        $trainingLocked = $lesson->getAttribute('library_training_locked');
                                        $currentStepKey = $lesson->getAttribute('library_next_step_key');
                                        $stepOrder = ['lesson' => 0, 'flashcards' => 1, 'quiz' => 2, 'training' => 3];
                                        $currentStepIndex = $stepOrder[$currentStepKey] ?? 0;
                                        $lessonUrl = route('lessons.show', $lesson);
                                        $flashcardsUrl = $lessonUrl.'#flashcards';
                                        $trainingUrl = $lesson->learning_category_id
                                            ? route('training.create', ['learning_domain_id' => $lesson->learning_domain_id, 'learning_category_id' => $lesson->learning_category_id])
                                            : route('training.create');
                                    @endphp

                                    <article class="rs-panel overflow-hidden rounded-[1.7rem] p-0">
                                        <div class="grid gap-0 lg:grid-cols-[13rem_1fr]">
                                            <div class="relative min-h-44 overflow-hidden">
                                                <img src="{{ $lesson->hero_image ? asset($lesson->hero_image) : asset('images/backgrounds/cours.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-70">
                                                <div class="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/30 to-transparent"></div>
                                                <div class="absolute bottom-4 left-4 right-4">
                                                    <x-lessons.status-badge :status="$statusKey" />
                                                </div>
                                            </div>

                                            <div class="flex flex-col justify-between gap-4 p-5">
                                                {{-- Tags + titre --}}
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-full border border-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-stone-300">
                                                            {{ $lesson->getAttribute('library_difficulty_label') }}
                                                        </span>
                                                        <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-amber-100">
                                                            {{ __('lessons.library.xp', ['xp' => $lesson->academic_xp_reward]) }}
                                                        </span>
                                                        @if ($lesson->getAttribute('library_linked_quest'))
                                                            <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-100">
                                                                {{ $lesson->getAttribute('library_linked_quest')->title }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <h4 class="rs-display mt-3 text-2xl text-stone-50">{{ $lesson->title }}</h4>
                                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-stone-400">{{ $lesson->summary }}</p>
                                                </div>

                                                {{-- Progression + actions --}}
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-lessons.progress-bar :value="$progress" class="flex-1" />
                                                        <span class="shrink-0 text-[11px] font-bold text-stone-500">{{ $progress }}%</span>
                                                        <span class="shrink-0 rounded-full border border-amber-200/20 bg-amber-200/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-amber-100">
                                                            {{ __("lessons.library.journey.steps.{$currentStepKey}.title") }}
                                                        </span>
                                                    </div>

                                                    <div class="flex flex-wrap gap-2">
                                                        <x-lessons.action-link :href="$lesson->getAttribute('library_next_action_url')">
                                                            {{ $lesson->getAttribute('library_next_action_label') }}
                                                        </x-lessons.action-link>

                                                        @if ($lesson->getAttribute('library_next_action_url') !== $lessonUrl)
                                                            <x-lessons.action-link :href="$lessonUrl" variant="secondary">
                                                                {{ __('lessons.library.actions.open') }}
                                                            </x-lessons.action-link>
                                                        @endif

                                                        @if (! $trainingLocked && $lesson->getAttribute('library_next_action_url') !== $trainingUrl)
                                                            <x-lessons.action-link :href="$trainingUrl" variant="secondary">
                                                                {{ __('lessons.library.actions.train') }}
                                                            </x-lessons.action-link>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </section>
            </main>
        </div>
    @endif
</div>
