<x-layouts::app :title="__('hub.page_title')">
    @php
        $avatarAssets = app(\App\Support\AvatarAssetCatalog::class);
        $user = auth()->user()->loadMissing(['avatar', 'userCompanions.companion']);
        $avatar = $user->avatar;
        $companions = $user->userCompanions->sortByDesc('is_favorite')->values();
        $districts = [
            [
                'key' => 'training',
                'status' => __('hub.status.priority'),
                'tone' => 'amber',
                'href' => route('training.create'),
                'image' => asset('images/backgrounds/training_center.png'),
            ],
            [
                'key' => 'lectures',
                'status' => __('hub.status.priority'),
                'tone' => 'teal',
                'href' => route('lessons.index'),
                'image' => asset('images/backgrounds/cours.png'),
            ],
            [
                'key' => 'adventure_map',
                'status' => __('hub.status.priority'),
                'tone' => 'sky',
                'href' => route('missions.index'),
                'image' => asset('images/backgrounds/world_map.png'),
            ],
            [
                'key' => 'echo_hall',
                'status' => __('hub.status.priority'),
                'tone' => 'violet',
                'href' => route('echoes.index'),
                'image' => asset('images/backgrounds/set3_1_0.png'),
            ],
            [
                'key' => 'quests',
                'status' => __('hub.status.coming_soon'),
                'tone' => 'emerald',
                'href' => null,
                'image' => asset('images/backgrounds/quests.png'),
            ],
            [
                'key' => 'collection',
                'status' => trans_choice('hub.collection_status', $companions->count(), ['count' => $companions->count()]),
                'tone' => 'rose',
                'href' => null,
                'image' => asset('images/backgrounds/friends.png'),
            ],
            [
                'key' => 'shop',
                'status' => __('hub.status.coming_soon'),
                'tone' => 'violet',
                'href' => null,
                'image' => asset('images/backgrounds/market.png'),
            ],
            [
                'key' => 'arena',
                'status' => __('hub.status.coming_soon'),
                'tone' => 'stone',
                'href' => null,
                'image' => asset('images/backgrounds/arena_1.png'),
            ],
        ];
        $districtColors = [
            'amber' => 'from-amber-300/20 to-transparent text-amber-100 ring-amber-200/20',
            'teal' => 'from-teal-300/20 to-transparent text-teal-100 ring-teal-200/20',
            'sky' => 'from-sky-300/20 to-transparent text-sky-100 ring-sky-200/20',
            'emerald' => 'from-emerald-300/20 to-transparent text-emerald-100 ring-emerald-200/20',
            'rose' => 'from-rose-300/20 to-transparent text-rose-100 ring-rose-200/20',
            'violet' => 'from-violet-300/20 to-transparent text-violet-100 ring-violet-200/20',
            'stone' => 'from-stone-300/20 to-transparent text-stone-100 ring-stone-200/20',
        ];
        $specializationColors = [
            'priority' => '#D97745',
            'signs' => '#F2C14E',
            'speed' => '#52B3D9',
            'safety' => '#65A30D',
            'dangers' => '#C2410C',
            'mixed' => '#A855F7',
        ];
        $avatarPresetKey = data_get($avatar->equipped_items, 'outfit_preset', data_get($avatar->equipped_items, 'outfit', $avatar->style));
        $avatarBodyKey = data_get($avatar->equipped_items, 'body', config('avatar.defaults.body'));
        $avatarHairKey = data_get($avatar->equipped_items, 'hair', config('avatar.defaults.hair'));
        $avatarBodyConfig = $avatarAssets->bodyAsset($avatarBodyKey) ?? [];
        $avatarHairConfig = $avatarAssets->hairAsset($avatarHairKey) ?? [];
        $avatarStyleLabel = data_get(
            $avatarAssets->outfitPresets(),
            $avatarPresetKey.'.label',
            ucfirst(str_replace(['_', '-'], ' ', $avatarPresetKey))
        );
        $featuredDistrict = collect($districts)->firstWhere('key', 'training');
        $lectureDistrict = collect($districts)->firstWhere('key', 'lectures');
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-5">
        <section class="rs-panel rs-hero rs-panel-enter relative overflow-hidden rounded-[2rem] p-5 sm:p-7">
            <div class="pointer-events-none absolute inset-0 opacity-35">
                <img src="{{ asset('images/backgrounds/set3_1_1.png') }}" alt="" class="h-full w-full object-cover mix-blend-soft-light">
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950 via-stone-950/88 to-stone-950/45"></div>
            </div>

            <div class="relative grid gap-5 lg:grid-cols-[1.25fr_0.95fr]">
                <div class="relative flex flex-col gap-5">
                    <div class="absolute -left-5 top-0 h-24 w-24 rounded-full bg-amber-300/12 blur-3xl"></div>
                    <div class="space-y-3">
                        <p class="rs-label">{{ __('hub.hero.eyebrow') }}</p>
                        <h1 class="rs-display text-4xl leading-none text-stone-50 sm:text-5xl">{{ __('hub.hero.title') }}</h1>
                        <p class="max-w-xl text-sm leading-7 text-stone-300 sm:text-base">
                            {{ __('hub.hero.body') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rs-stat-chip">
                            <p class="rs-label">{{ __('hub.stats.player_level') }}</p>
                            <p class="mt-2 text-lg font-semibold text-stone-50">Lv. {{ $user->level }}</p>
                        </div>
                        <div class="rs-stat-chip">
                            <p class="rs-label">{{ __('hub.stats.style') }}</p>
                            <p class="mt-2 text-lg font-semibold capitalize text-stone-50">{{ $avatarStyleLabel }}</p>
                        </div>
                    </div>

                    <div class="rounded-[1.6rem] border border-amber-200/16 bg-stone-950/55 p-4 shadow-2xl shadow-stone-950/30 backdrop-blur">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="rs-label">{{ __('hub.briefing.eyebrow') }}</p>
                                <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ __('hub.briefing.title') }}</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-300">{{ __('hub.briefing.body') }}</p>
                            </div>
                            <a href="{{ route('lessons.index') }}" class="inline-flex shrink-0 rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                                {{ __('hub.briefing.cta') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="rs-avatar-crest min-h-[18rem] rounded-[1.8rem] border border-white/8 p-4">
                    <div class="absolute right-5 top-4 z-20">
                        <span class="rs-badge">{{ __('hub.city.badge') }}</span>
                    </div>
                    <div class="rs-orb right-10 top-8 h-16 w-16 bg-sky-300/25"></div>
                    <div class="rs-orb bottom-10 left-6 h-20 w-20 bg-amber-300/16" style="animation-delay: 0.7s;"></div>

                    <div class="relative grid h-full min-h-[16rem] gap-4 rounded-[1.4rem] bg-[linear-gradient(180deg,_rgba(16,20,25,0.10)_0%,_rgba(22,19,18,0.70)_58%,_rgba(18,16,15,0.98)_100%)] p-4 md:grid-cols-[0.78fr_1fr]">
                        <div class="relative flex min-h-[14rem] items-center justify-center overflow-hidden rounded-[1.2rem] border border-white/6 bg-white/5">
                            <div class="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.16),_transparent_68%)]"></div>
                            <div class="absolute bottom-4 left-6 right-6 h-12 rounded-full bg-black/25 blur-xl"></div>
                            <x-onboarding.avatar-mini-preview
                                class="rs-hub-avatar-preview relative z-10"
                                :body-key="$avatarBodyKey"
                                :hair-key="$avatarHairKey"
                                :outfit-preset-key="$avatarPresetKey"
                                :equipped-items="$avatar->equipped_items ?? []"
                                :skin="data_get($avatar->colors, 'skin', data_get($avatarBodyConfig, 'skin', config('avatar.defaults.skin')))"
                                :hair-color="data_get($avatar->colors, 'hair', data_get($avatarHairConfig, 'color', config('avatar.defaults.hair_color')))"
                                :accent="data_get($avatar->colors, 'accent', '#D97745')"
                                :hair-scale="data_get($avatarBodyConfig, 'hair_scale', '1.00')"
                                :hair-offset-y="data_get($avatarBodyConfig, 'hair_offset_y', '0%')"
                            />
                        </div>

                        <div class="flex flex-col justify-between gap-4 rounded-[1.2rem] border border-white/6 bg-stone-950/55 p-4 backdrop-blur">
                            <div>
                                <p class="rs-label">Fiche d’escouade</p>
                                <h2 class="rs-display mt-1 text-2xl text-stone-50">{{ $avatar->nickname ?: __('hub.default_nickname') }}</h2>
                                <p class="mt-2 text-sm leading-6 text-stone-400">{{ $companions->count() }}/3 compagnons prêts pour les premières missions.</p>
                            </div>

                            <div class="space-y-2">
                                @foreach ($companions->take(3) as $userCompanion)
                                    @php
                                        $slug = $userCompanion->companion->specializationCategory->slug;
                                        $accent = $specializationColors[$slug] ?? '#D97745';
                                    @endphp
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/6 bg-white/5 px-3 py-2">
                                        <div class="flex min-w-0 items-center gap-3">
                                                <img src="{{ $userCompanion->companion->sprite_url }}" alt="{{ $userCompanion->companion->name }}" class="h-8 w-8 shrink-0 rounded-xl object-cover">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-stone-100">{{ $userCompanion->companion->name }}</p>
                                                <p class="truncate text-xs text-stone-500">{{ __("companions.roles.{$userCompanion->companion->role->value}") }}</p>
                                            </div>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold text-stone-300">Lv. {{ $userCompanion->level }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-2xl bg-white/5 px-3 py-2 text-center">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-stone-400">{{ __('hub.city.xp') }}</p>
                                    <p class="mt-1 text-base font-semibold text-stone-100">{{ $user->xp }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/5 px-3 py-2 text-center">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-stone-400">{{ __('hub.city.coins') }}</p>
                                    <p class="mt-1 text-base font-semibold text-stone-100">{{ $user->coins }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="rs-label">{{ __('hub.map.eyebrow') }}</p>
                    <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ __('hub.map.title') }}</h2>
                </div>
                <span class="rs-badge hidden sm:inline-flex">{{ __('hub.map.badge') }}</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($districts as $index => $district)
                        <article class="rs-map-card rs-panel-enter overflow-hidden rounded-[1.75rem] p-0" style="animation-delay: {{ $index * 80 }}ms;">
                            <div class="relative h-32 overflow-hidden">
                                <img src="{{ $district['image'] }}" alt="" class="h-full w-full object-cover opacity-70 transition duration-300 hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/34 to-transparent"></div>
                                <span class="absolute right-3 top-3 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $districtColors[$district['tone']] }}">
                                    {{ $district['status'] }}
                                </span>
                            </div>

                            <div class="p-4">
                                <div class="space-y-1">
                                    <p class="rs-label">{{ __('hub.map.building') }}</p>
                                    <h3 class="rs-display text-2xl text-stone-50">{{ __("hub.districts.{$district['key']}.title") }}</h3>
                                </div>

                                <div class="rs-divider my-3"></div>

                                <p class="line-clamp-3 text-sm leading-6 text-stone-300">{{ __("hub.districts.{$district['key']}.description") }}</p>

                                @if ($district['href'] ?? null)
                                    <a href="{{ $district['href'] }}" class="mt-4 inline-flex rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                                        {{ __("hub.districts.{$district['key']}.cta") }}
                                    </a>
                                @else
                                    <span class="mt-4 inline-flex rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-400">
                                        {{ __("hub.districts.{$district['key']}.cta") }}
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1fr_0.9fr]">
            <article class="rs-panel rounded-[1.9rem] p-5 sm:p-6">
                <div class="grid gap-4 lg:grid-cols-[0.7fr_1fr] lg:items-center">
                    <div>
                        <p class="rs-label">{{ __('hub.guidance.eyebrow') }}</p>
                        <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ __('hub.guidance.title') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-stone-400">{{ __('hub.guidance.body') }}</p>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (trans('hub.guidance.items') as $hint)
                            <div class="rounded-[1.2rem] border border-white/8 bg-white/5 px-4 py-3 text-sm leading-6 text-stone-300">
                                {{ $hint }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="rs-panel relative overflow-hidden rounded-[1.9rem] p-5 sm:p-6">
                <img src="{{ $lectureDistrict['image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950 via-stone-950/88 to-stone-950/55"></div>
                <div class="relative">
                    <p class="rs-label">{{ __('hub.classroom.eyebrow') }}</p>
                    <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ __('hub.classroom.title') }}</h2>
                    <p class="mt-3 text-sm leading-7 text-stone-300">{{ __('hub.classroom.body') }}</p>
                    <a href="{{ route('lessons.index') }}" class="mt-4 inline-flex rounded-full bg-amber-200 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-950 transition hover:bg-amber-100">
                        {{ __('hub.classroom.cta') }}
                    </a>
                </div>
            </article>
        </section>

        <section class="rs-panel relative overflow-hidden rounded-[1.9rem] p-5 sm:p-6">
            <img src="{{ asset('images/backgrounds/friends.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-12">
            <div class="absolute inset-0 bg-gradient-to-r from-stone-950/96 via-stone-950/90 to-stone-950/76"></div>
            <div class="relative">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="rs-label">{{ __('hub.team.eyebrow') }}</p>
                    <h2 class="rs-display mt-2 text-3xl text-stone-50">{{ __('hub.team.title') }}</h2>
                </div>
                <p class="text-sm text-stone-400">{{ __('hub.team.body') }}</p>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                @foreach ($companions as $userCompanion)
                    @php
                        $slug = $userCompanion->companion->specializationCategory->slug;
                        $accent = $specializationColors[$slug] ?? '#D97745';
                    @endphp
                    <article class="rs-companion-card rounded-[1.7rem] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $userCompanion->companion->sprite_url }}" alt="{{ $userCompanion->companion->name }}" class="h-12 w-12 shrink-0 rounded-[1.1rem] object-cover">
                                <div>
                                    <p class="text-lg font-semibold text-stone-50">{{ $userCompanion->companion->name }}</p>
                                    <p class="text-sm capitalize text-stone-400">{{ __("companions.roles.{$userCompanion->companion->role->value}") }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                @if ($userCompanion->is_favorite)
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-100">
                                        {{ __('hub.team.favorite') }}
                                    </span>
                                @endif

                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-950" style="background-color: {{ $accent }};">
                                    {{ $userCompanion->companion->specializationCategory->name }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-white/4 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-stone-500">{{ __('hub.team.level') }}</p>
                                <p class="mt-1 text-base font-semibold text-stone-100">{{ $userCompanion->level }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/4 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-stone-500">{{ __('hub.team.hp') }}</p>
                                <p class="mt-1 text-base font-semibold text-stone-100">{{ $userCompanion->current_hp }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            </div>
        </section>
    </div>
</x-layouts::app>
