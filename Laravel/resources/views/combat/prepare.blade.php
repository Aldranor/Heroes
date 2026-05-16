<x-layouts::app title="Préparation du combat">
    @php
        $heroActive = old('hero_active_skills', $heroBuild['loadout']['active']);
        $heroPassives = old('hero_passive_skills', $heroBuild['loadout']['passive']);
        $heroUltimate = old('hero_ultimate_skill', $heroBuild['loadout']['ultimate']);
        $selectedIds = collect(old('user_companion_ids', $selectedCompanionIds))->map(fn ($id) => (int) $id)->all();
    @endphp

    <div class="combat-prep-page">
        <section class="combat-prep-hero rs-panel rs-panel-enter rounded-[2rem]">
            <p class="combat-prep-kicker">Préparation</p>
            <h1 class="combat-prep-title rs-display">{{ $node->title }}</h1>
            <p class="combat-prep-body">
                Prépare la composition, les spécialisations et les loadouts avant d’entrer en combat. Les choix équipés ici définissent réellement le style de jeu.
            </p>

            <div class="combat-prep-hero__meta">
                <span class="combat-prep-pill">{{ $node->getAttribute('ui_badge') ?? 'Combat' }}</span>
                @if ($node->learningCategory)
                    <span class="combat-prep-pill">{{ $node->learningCategory->name }}</span>
                @endif
                <span class="combat-prep-pill">4 actifs · 3 passifs · 1 ultime</span>
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-[1.2rem] border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ $errors->first('combat') ?: $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('combat.store') }}" class="combat-prep-layout">
            @csrf
            <input type="hidden" name="adventure_node_id" value="{{ $node->id }}">

            <div class="combat-prep-main">
                <section class="combat-prep-panel rs-panel rounded-[1.7rem]">
                    <div class="combat-prep-panel__head">
                        <div>
                            <h2 class="combat-prep-panel__title rs-display">Héros</h2>
                            <p class="combat-prep-panel__subtitle">
                                Définis la classe, la spécialisation et le loadout actif du héros.
                            </p>
                        </div>
                        <span class="combat-prep-pill">{{ $heroBuild['role_label'] }}</span>
                    </div>

                    <div class="combat-prep-grid">
                        <label class="combat-prep-field">
                            <span class="combat-prep-label">Classe</span>
                            <select name="hero_class" class="combat-prep-select">
                                @foreach ($heroBuild['class_options'] as $classOption)
                                    <option value="{{ $classOption['key'] }}" @selected(old('hero_class', $heroBuild['class_key']) === $classOption['key'])>
                                        {{ $classOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="combat-prep-field">
                            <span class="combat-prep-label">Spécialisation</span>
                            <select name="hero_specialization" class="combat-prep-select">
                                @foreach ($heroBuild['specialization_options'] as $spec)
                                    <option value="{{ $spec['key'] }}" @selected(old('hero_specialization', $heroBuild['specialization_key']) === $spec['key'])>
                                        {{ $spec['label'] }} · {{ $spec['role_label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="combat-prep-tags">
                        @foreach ($heroBuild['specialization_focus_tags'] as $tag)
                            <span class="combat-prep-tag">{{ $tag['label'] }}</span>
                        @endforeach
                    </div>

                    <div class="combat-prep-loadout">
                        <div class="combat-prep-slot">
                            <div class="combat-prep-slot__head">
                                <span class="combat-prep-label">Actifs équipés</span>
                                <span class="combat-prep-slot__count">Max 4</span>
                            </div>
                            <div class="combat-prep-skill-grid">
                                @foreach ($heroBuild['available_by_slot']['active'] as $skill)
                                    <label class="combat-prep-skill-option">
                                        <input type="checkbox" name="hero_active_skills[]" value="{{ $skill['slug'] }}" @checked(in_array($skill['slug'], $heroActive, true))>
                                        <span>
                                            <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                            <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                            <span class="combat-prep-skill-option__tags">
                                                @foreach ($skill['tag_labels'] as $tagLabel)
                                                    <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                @endforeach
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="combat-prep-slot">
                            <div class="combat-prep-slot__head">
                                <span class="combat-prep-label">Passifs équipés</span>
                                <span class="combat-prep-slot__count">Max 3</span>
                            </div>
                            <div class="combat-prep-skill-grid">
                                @foreach ($heroBuild['available_by_slot']['passive'] as $skill)
                                    <label class="combat-prep-skill-option">
                                        <input type="checkbox" name="hero_passive_skills[]" value="{{ $skill['slug'] }}" @checked(in_array($skill['slug'], $heroPassives, true))>
                                        <span>
                                            <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                            <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                            <span class="combat-prep-skill-option__tags">
                                                @foreach ($skill['tag_labels'] as $tagLabel)
                                                    <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                @endforeach
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="combat-prep-slot">
                            <div class="combat-prep-slot__head">
                                <span class="combat-prep-label">Ultime</span>
                                <span class="combat-prep-slot__count">Max 1</span>
                            </div>
                            <div class="combat-prep-skill-grid">
                                @foreach ($heroBuild['available_by_slot']['ultimate'] as $skill)
                                    <label class="combat-prep-skill-option">
                                        <input type="radio" name="hero_ultimate_skill" value="{{ $skill['slug'] }}" @checked($heroUltimate === $skill['slug'])>
                                        <span>
                                            <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                            <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                            <span class="combat-prep-skill-option__tags">
                                                @foreach ($skill['tag_labels'] as $tagLabel)
                                                    <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                @endforeach
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                @foreach ($companions as $companion)
                    @php
                        $companionSelected = in_array($companion['id'], $selectedIds, true);
                        $companionActive = old('companions.'.$companion['id'].'.active_skills', $companion['loadout']['active']);
                        $companionPassives = old('companions.'.$companion['id'].'.passive_skills', $companion['loadout']['passive']);
                        $companionUltimate = old('companions.'.$companion['id'].'.ultimate_skill', $companion['loadout']['ultimate']);
                    @endphp

                    <section class="combat-prep-panel combat-prep-companion rs-panel rounded-[1.7rem]">
                        <div class="combat-prep-companion__select">
                            <input type="checkbox" name="user_companion_ids[]" value="{{ $companion['id'] }}" @checked($companionSelected)>
                            <img src="{{ $companion['sprite_url'] }}" alt="" class="combat-prep-companion__portrait">
                            <div class="min-w-0 flex-1">
                                <h2 class="combat-prep-panel__title rs-display">{{ $companion['name'] }}</h2>
                                <p class="combat-prep-panel__subtitle">
                                    {{ $companion['class_label'] }} · {{ $companion['role_label'] }} · Niv. {{ $companion['level'] }}
                                </p>
                            </div>
                        </div>

                        <div class="combat-prep-grid">
                            <label class="combat-prep-field">
                                <span class="combat-prep-label">Spécialisation</span>
                                <select name="companions[{{ $companion['id'] }}][specialization_slug]" class="combat-prep-select">
                                    @foreach ($companion['specialization_options'] as $spec)
                                        <option value="{{ $spec['key'] }}" @selected(old('companions.'.$companion['id'].'.specialization_slug', $companion['specialization_key']) === $spec['key'])>
                                            {{ $spec['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="combat-prep-field">
                                <span class="combat-prep-label">Position</span>
                                <select name="companions[{{ $companion['id'] }}][position]" class="combat-prep-select">
                                    <option value="2" @selected((int) old('companions.'.$companion['id'].'.position', $companion['position']) === 2)>Ligne 2</option>
                                    <option value="3" @selected((int) old('companions.'.$companion['id'].'.position', $companion['position']) === 3)>Ligne 3</option>
                                </select>
                            </label>
                        </div>

                        <div class="combat-prep-tags">
                            @foreach ($companion['specialization_focus_tags'] as $tag)
                                <span class="combat-prep-tag">{{ $tag['label'] }}</span>
                            @endforeach
                        </div>

                        <div class="combat-prep-loadout">
                            @if ($companion['available_by_slot']['active'] !== [])
                                <div class="combat-prep-slot">
                                    <div class="combat-prep-slot__head">
                                        <span class="combat-prep-label">Actifs</span>
                                        <span class="combat-prep-slot__count">Max 4</span>
                                    </div>
                                    <div class="combat-prep-skill-grid">
                                        @foreach ($companion['available_by_slot']['active'] as $skill)
                                            <label class="combat-prep-skill-option">
                                                <input type="checkbox" name="companions[{{ $companion['id'] }}][active_skills][]" value="{{ $skill['slug'] }}" @checked(in_array($skill['slug'], $companionActive, true))>
                                                <span>
                                                    <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                                    <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                                    <span class="combat-prep-skill-option__tags">
                                                        @foreach ($skill['tag_labels'] as $tagLabel)
                                                            <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($companion['available_by_slot']['passive'] !== [])
                                <div class="combat-prep-slot">
                                    <div class="combat-prep-slot__head">
                                        <span class="combat-prep-label">Passifs</span>
                                        <span class="combat-prep-slot__count">Max 3</span>
                                    </div>
                                    <div class="combat-prep-skill-grid">
                                        @foreach ($companion['available_by_slot']['passive'] as $skill)
                                            <label class="combat-prep-skill-option">
                                                <input type="checkbox" name="companions[{{ $companion['id'] }}][passive_skills][]" value="{{ $skill['slug'] }}" @checked(in_array($skill['slug'], $companionPassives, true))>
                                                <span>
                                                    <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                                    <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                                    <span class="combat-prep-skill-option__tags">
                                                        @foreach ($skill['tag_labels'] as $tagLabel)
                                                            <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($companion['available_by_slot']['ultimate'] !== [])
                                <div class="combat-prep-slot">
                                    <div class="combat-prep-slot__head">
                                        <span class="combat-prep-label">Ultime</span>
                                        <span class="combat-prep-slot__count">Max 1</span>
                                    </div>
                                    <div class="combat-prep-skill-grid">
                                        @foreach ($companion['available_by_slot']['ultimate'] as $skill)
                                            <label class="combat-prep-skill-option">
                                                <input type="radio" name="companions[{{ $companion['id'] }}][ultimate_skill]" value="{{ $skill['slug'] }}" @checked($companionUltimate === $skill['slug'])>
                                                <span>
                                                    <span class="combat-prep-skill-option__title">{{ $skill['label'] }}</span>
                                                    <span class="combat-prep-skill-option__meta">{{ $skill['description'] }}</span>
                                                    <span class="combat-prep-skill-option__tags">
                                                        @foreach ($skill['tag_labels'] as $tagLabel)
                                                            <span class="combat-prep-skill-option__tag">{{ $tagLabel }}</span>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="combat-prep-sidebar">
                <section class="combat-prep-summary rs-panel rounded-[1.7rem]">
                    <p class="combat-prep-kicker">Puissance estimée</p>
                    <p class="combat-prep-summary__value rs-display">{{ $estimatedPower }}</p>
                    <p class="combat-prep-note">Estimation basée sur les rôles, les spécialisations, les passifs, les ultimes et les synergies d’équipe actuellement préparés.</p>
                </section>

                <section class="combat-prep-summary rs-panel rounded-[1.7rem]">
                    <p class="combat-prep-kicker">Synergies actives</p>
                    <div class="combat-prep-list">
                        @forelse ($synergies as $synergy)
                            <div class="combat-prep-list__item">
                                <strong>{{ $synergy['label'] }}</strong><br>
                                {{ $synergy['description'] }}
                            </div>
                        @empty
                            <div class="combat-prep-list__item">Aucune synergie majeure n’est active avec cette préparation.</div>
                        @endforelse
                    </div>
                </section>

                <section class="combat-prep-summary rs-panel rounded-[1.7rem]">
                    <p class="combat-prep-kicker">Forces</p>
                    <div class="combat-prep-list">
                        @foreach ($strengths as $strength)
                            <div class="combat-prep-list__item">{{ $strength }}</div>
                        @endforeach
                    </div>

                    <p class="combat-prep-kicker mt-4">Faiblesses</p>
                    <div class="combat-prep-list">
                        @foreach ($weaknesses as $weakness)
                            <div class="combat-prep-list__item">{{ $weakness }}</div>
                        @endforeach
                    </div>
                </section>

                <section class="combat-prep-summary rs-panel rounded-[1.7rem]">
                    <p class="combat-prep-kicker">Validation</p>
                    <div class="combat-prep-actions">
                        <button type="submit" class="combat-prep-button">Entrer en combat</button>
                        <a href="{{ route('skills.index') }}" class="combat-prep-button text-center">Arbres et talents</a>
                    </div>
                    <p class="combat-prep-note">Les compagnons non cochés ne partent pas. Les actifs sont limités à 4, les passifs à 3 et un seul ultime peut être équipé.</p>
                </section>
            </aside>
        </form>
    </div>
</x-layouts::app>
