<x-layouts::app :title="__('training.page_title')">
    @php
        $requestedDomainId = request()->integer('learning_domain_id') ?: null;
        $requestedCategoryId = request()->integer('learning_category_id') ?: null;
        $requestedMode = request('mode') ?: ($recommendation['mode'] ?? null);
        $selectedDomainId = (int) old('learning_domain_id', $requestedDomainId ?? $domains->first()?->id);
        $selectedDomain = $domains->firstWhere('id', $selectedDomainId) ?? $domains->first();
        $selectedCategoryId = (int) old('learning_category_id', $requestedCategoryId ?? $selectedDomain?->categories->first()?->id);
        $selectedCompanionId = (int) old('user_companion_id', $companions->first()?->id);
        $selectedMode = old('mode', $requestedMode ?? $modes->first()['key']);
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-5">
        <section class="rs-panel relative overflow-hidden rounded-[2rem] p-5 sm:p-7">
            <div class="pointer-events-none absolute inset-0">
                <img src="{{ asset('images/backgrounds/training.png') }}" alt="" class="h-full w-full scale-110 object-cover opacity-60 blur-[1.5px] saturate-125">
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950/96 via-stone-950/78 to-stone-950/45"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-stone-950/55 via-transparent to-stone-950/95"></div>
            </div>

            <div class="relative grid gap-5 lg:grid-cols-[1fr_0.78fr] lg:items-stretch">
                <div class="flex flex-col justify-between gap-5">
                    <div>
                        <p class="rs-label">Briefing de mission</p>
                        <h1 class="rs-display mt-2 text-4xl leading-none text-stone-50 sm:text-5xl">Centre d’entraînement</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-300 sm:text-base">
                            Prépare une session rapide, choisis un mode ciblé et renforce exactement les points qui coincent.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] border border-white/8 bg-white/5 px-4 py-3">
                            <p class="rs-label">Format</p>
                            <p class="mt-2 text-sm font-semibold text-stone-100">5 questions</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-white/8 bg-white/5 px-4 py-3">
                            <p class="rs-label">Modes</p>
                            <p class="mt-2 text-sm font-semibold text-stone-100">{{ $modes->count() - 1 }} ciblés</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-white/8 bg-white/5 px-4 py-3">
                            <p class="rs-label">Skills</p>
                            <p class="mt-2 text-sm font-semibold text-stone-100">4 tactiques</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-white/8 bg-white/5 px-4 py-3">
                            <p class="rs-label">Récompenses</p>
                            <p class="mt-2 text-sm font-semibold text-stone-100">XP + pièces</p>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.6rem] border border-amber-200/18 bg-stone-950/45 px-5 py-5 shadow-2xl shadow-stone-950/40 backdrop-blur-md">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/8 via-transparent to-amber-300/8"></div>
                    <div class="relative">
                        <p class="rs-label">Doctrine</p>
                        <h2 class="rs-display mt-2 text-2xl text-stone-50">Progression ciblée</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-300">
                            Bibliothèque pour apprendre, entraînement pour automatiser. Les erreurs renvoient vers les bonnes cartes, le bon cours et la bonne session.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full border border-amber-200/20 bg-amber-200/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-amber-100">Feedback immédiat</span>
                            <span class="rounded-full border border-white/10 bg-white/6 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-200">Révision intégrée</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($recommendation)
            <section class="rs-panel rounded-[1.7rem] border border-amber-200/20 bg-amber-200/10 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="rs-label">Recommandation</p>
                        <p class="mt-2 text-lg font-semibold text-stone-50">{{ $recommendation['reason'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-stone-300">Le formulaire ci-dessous est déjà orienté vers la bonne catégorie et le bon mode.</p>
                    </div>
                    @if ($recommendation['lesson_url'])
                        <a href="{{ $recommendation['lesson_url'] }}" class="inline-flex justify-center rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 transition hover:border-amber-200/30 hover:text-amber-50">
                            Ouvrir le cours conseillé
                        </a>
                    @endif
                </div>
            </section>
        @endif

        <section class="rs-panel relative overflow-hidden rounded-[1.9rem] p-5 sm:p-6">
            <div class="pointer-events-none absolute inset-0">
                <img src="{{ asset('images/backgrounds/training.png') }}" alt="" class="h-full w-full scale-110 object-cover opacity-55 blur-[1.5px] saturate-125">
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950/96 via-stone-950/84 to-stone-950/62"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-stone-950/65 via-transparent to-stone-950/95"></div>
            </div>

            <div class="relative">
                @if ($errors->any())
                    <div class="mb-5 rounded-[1.2rem] border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($domains->isEmpty() || $companions->isEmpty())
                    <div class="rounded-[1.4rem] border border-amber-200/20 bg-amber-300/10 px-4 py-4 text-sm leading-6 text-amber-50">
                        {{ __('training.choose.empty') }}
                    </div>
                @else
                    <form method="POST" action="{{ route('training.store') }}" class="grid gap-5">
                        @csrf

                        <div class="grid gap-4 lg:grid-cols-3">
                            <label class="grid gap-2 rounded-[1.4rem] border border-white/10 bg-stone-950/42 p-4 shadow-xl shadow-stone-950/20 backdrop-blur-md">
                                <span class="rs-label">{{ __('training.choose.domain') }}</span>
                                <span class="text-xs leading-5 text-stone-500">Le terrain général de la session.</span>
                                <select name="learning_domain_id" required class="rounded-[1.1rem] border border-white/10 bg-stone-950/60 px-4 py-3 text-sm text-stone-100 outline-none focus:border-amber-200/50" data-training-domain-select>
                                    @foreach ($domains as $domain)
                                        <option value="{{ $domain->id }}" @selected($selectedDomainId === $domain->id)>
                                            {{ $domain->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-2 rounded-[1.4rem] border border-white/10 bg-stone-950/42 p-4 shadow-xl shadow-stone-950/20 backdrop-blur-md">
                                <span class="rs-label">{{ __('training.choose.category') }}</span>
                                <span class="text-xs leading-5 text-stone-500">L’objectif précis à travailler.</span>
                                <select name="learning_category_id" required class="rounded-[1.1rem] border border-white/10 bg-stone-950/60 px-4 py-3 text-sm text-stone-100 outline-none focus:border-amber-200/50" data-training-category-select>
                                    @foreach ($domains as $domain)
                                        <optgroup label="{{ $domain->name }}">
                                            @foreach ($domain->categories as $category)
                                                @php
                                                    $isLocked = $category->getAttribute('training_unlocked') === false;
                                                    $requiredLessonId = $category->getAttribute('required_lesson_id');
                                                @endphp
                                                <option
                                                    value="{{ $category->id }}"
                                                    data-domain-id="{{ $domain->id }}"
                                                    data-locked="{{ $isLocked ? '1' : '0' }}"
                                                    data-required-lesson-url="{{ $requiredLessonId ? route('lessons.show', $requiredLessonId) : '' }}"
                                                    data-mastery-label="{{ $category->getAttribute('mastery_label') }}"
                                                    data-mastery-score="{{ $category->getAttribute('mastery_score') }}"
                                                    @selected($selectedCategoryId === $category->id)
                                                >
                                                    {{ $category->name }} · {{ $category->getAttribute('mastery_label') }}{{ $isLocked ? ' · leçon requise' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-2 rounded-[1.4rem] border border-white/10 bg-stone-950/42 p-4 shadow-xl shadow-stone-950/20 backdrop-blur-md">
                                <span class="rs-label">{{ __('training.choose.companion') }}</span>
                                <span class="text-xs leading-5 text-stone-500">L’allié qui progressera avec vous.</span>
                                <select name="user_companion_id" required class="rounded-[1.1rem] border border-white/10 bg-stone-950/60 px-4 py-3 text-sm text-stone-100 outline-none focus:border-amber-200/50">
                                    @foreach ($companions as $userCompanion)
                                        <option value="{{ $userCompanion->id }}" @selected($selectedCompanionId === $userCompanion->id)>
                                            {{ $userCompanion->companion->name }} · Lv. {{ $userCompanion->level }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-[1fr_22rem]">
                            <div class="rounded-[1.6rem] border border-white/10 bg-stone-950/42 p-4 shadow-xl shadow-stone-950/20 backdrop-blur-md">
                                <div class="flex items-end justify-between gap-3">
                                    <div>
                                        <p class="rs-label">Mode</p>
                                        <h2 class="mt-2 text-lg font-semibold text-stone-50">Format de session</h2>
                                    </div>
                                    <p class="text-xs text-stone-500">Ciblé ou mixte</p>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($modes as $mode)
                                        <label class="group cursor-pointer">
                                            <input type="radio" name="mode" value="{{ $mode['key'] }}" class="peer sr-only" @checked($selectedMode === $mode['key'])>
                                            <span class="flex h-full min-h-28 flex-col justify-between rounded-[1.25rem] border border-white/10 bg-white/5 p-4 text-left transition peer-checked:border-amber-200/40 peer-checked:bg-amber-200/10 group-hover:border-white/20">
                                                <span>
                                                    <span class="text-sm font-semibold text-stone-50">{{ $mode['label'] }}</span>
                                                    <span class="mt-2 block text-xs leading-5 text-stone-400">{{ $mode['body'] }}</span>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <aside class="rounded-[1.6rem] border border-amber-200/18 bg-amber-200/10 p-4 shadow-xl shadow-amber-950/20 backdrop-blur-md">
                                <p class="rs-label">Lecture rapide</p>
                                <div class="mt-3 grid gap-3">
                                    <div class="rounded-[1.1rem] border border-white/10 bg-stone-950/35 px-3 py-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Maîtrise</p>
                                        <p data-training-mastery-label class="mt-2 text-sm font-semibold text-stone-50">-</p>
                                        <p data-training-mastery-score class="mt-1 text-xs text-stone-400">0%</p>
                                    </div>
                                    <div class="rounded-[1.1rem] border border-white/10 bg-stone-950/35 px-3 py-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Mode</p>
                                        <p data-training-mode-label class="mt-2 text-sm font-semibold text-stone-50">-</p>
                                    </div>
                                    <div class="rounded-[1.1rem] border border-white/10 bg-stone-950/35 px-3 py-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">Session</p>
                                        <p class="mt-2 text-sm font-semibold text-stone-50">5 décisions · timer · skills</p>
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <div data-training-lock-notice class="hidden rounded-[1.4rem] border border-amber-200/25 bg-amber-300/10 p-4 text-sm leading-6 text-amber-50">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold uppercase tracking-[0.16em]">{{ __('lessons.training_locked.title') }}</p>
                                    <p class="mt-1 text-amber-50/85">{{ __('lessons.training_locked.body') }}</p>
                                </div>
                                <a data-training-lock-link href="{{ route('lessons.index') }}" class="inline-flex shrink-0 rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950">
                                    {{ __('lessons.training_locked.open_lesson') }}
                                </a>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 rounded-[1.4rem] border border-amber-200/18 bg-amber-200/10 p-4 shadow-xl shadow-amber-950/20 backdrop-blur-md sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="rs-label">Prêt au départ</p>
                                <p class="mt-1 text-sm leading-6 text-stone-300">Correction immédiate, deck de révision et relance ciblée après chaque erreur.</p>
                            </div>
                            <button type="submit" data-training-start-button class="shrink-0 rounded-full bg-amber-200 px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:bg-stone-600 disabled:text-stone-300">
                                Lancer la mission
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <script>
        (() => {
            const domainSelect = document.querySelector('[data-training-domain-select]');
            const categorySelect = document.querySelector('[data-training-category-select]');
            const lockNotice = document.querySelector('[data-training-lock-notice]');
            const lockLink = document.querySelector('[data-training-lock-link]');
            const startButton = document.querySelector('[data-training-start-button]');
            const masteryLabel = document.querySelector('[data-training-mastery-label]');
            const masteryScore = document.querySelector('[data-training-mastery-score]');
            const modeLabel = document.querySelector('[data-training-mode-label]');

            if (!domainSelect || !categorySelect) {
                return;
            }

            const syncSummary = () => {
                const categoryOption = categorySelect.selectedOptions[0];
                const modeInput = document.querySelector('input[name="mode"]:checked');
                const modeCardLabel = modeInput?.closest('label')?.querySelector('.text-sm');
                const isLocked = categoryOption?.dataset.locked === '1';

                if (masteryLabel) {
                    masteryLabel.textContent = categoryOption?.dataset.masteryLabel || '-';
                }

                if (masteryScore) {
                    masteryScore.textContent = `${categoryOption?.dataset.masteryScore || 0}%`;
                }

                if (modeLabel) {
                    modeLabel.textContent = modeCardLabel?.textContent?.trim() || '-';
                }

                if (lockNotice) {
                    lockNotice.classList.toggle('hidden', !isLocked);
                }

                if (lockLink && categoryOption?.dataset.requiredLessonUrl) {
                    lockLink.href = categoryOption.dataset.requiredLessonUrl;
                }

                if (startButton) {
                    startButton.disabled = isLocked;
                }
            };

            const syncCategories = () => {
                const activeDomainId = domainSelect.value;
                let firstVisible = null;

                Array.from(categorySelect.options).forEach((option) => {
                    const visible = !option.dataset.domainId || option.dataset.domainId === activeDomainId;
                    option.hidden = !visible;

                    if (visible && !firstVisible) {
                        firstVisible = option;
                    }
                });

                const selectedOption = categorySelect.selectedOptions[0];

                if (!selectedOption || selectedOption.hidden) {
                    if (firstVisible) {
                        categorySelect.value = firstVisible.value;
                    }
                }

                syncSummary();
            };

            domainSelect.addEventListener('change', syncCategories);
            categorySelect.addEventListener('change', syncSummary);

            document.querySelectorAll('input[name="mode"]').forEach((input) => {
                input.addEventListener('change', syncSummary);
            });

            syncCategories();
        })();
    </script>
</x-layouts::app>
