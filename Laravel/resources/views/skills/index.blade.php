<x-layouts::app title="Arbre de compétences">
    <div class="skill-tree-page">
        @if ($activeTree)
            <section class="skill-tree-stage rs-panel rs-panel-enter rounded-[2rem]">
                <div class="skill-tree-topbar">
                    <div class="skill-tree-topbar__lead">
                        <p class="skill-tree-topbar__eyebrow">Menu</p>
                        <h1 class="skill-tree-topbar__title rs-display">Aptitudes</h1>
                    </div>

                    <div class="skill-tree-topbar__stack">
                        @if ($subjects !== [])
                            <nav class="skill-tree-subject-switcher" aria-label="Personnages">
                                @foreach ($subjects as $subject)
                                    <a
                                        href="{{ route('skills.index', ['subject' => $subject['key'], 'tree' => $activeTree['slug']]) }}"
                                        class="skill-tree-subject-switcher__link {{ $subject['is_active'] ? 'skill-tree-subject-switcher__link--active' : '' }}"
                                    >
                                        <span class="skill-tree-subject-switcher__label">{{ $subject['label'] }}</span>
                                        <span class="skill-tree-subject-switcher__name">{{ $subject['name'] }}</span>
                                    </a>
                                @endforeach
                            </nav>
                        @endif

                        @if ($skillTrees !== [])
                            <nav class="skill-tree-switcher" aria-label="Arbres de compétences">
                                @foreach ($skillTrees as $tree)
                                    <a
                                        href="{{ route('skills.index', ['subject' => $activeSubject['key'], 'tree' => $tree['slug']]) }}"
                                        class="skill-tree-switcher__link {{ $tree['is_active'] ? 'skill-tree-switcher__link--active' : '' }}"
                                    >
                                        {{ $tree['name'] }}
                                    </a>
                                @endforeach
                            </nav>
                        @endif
                    </div>
                </div>

                <div class="skill-tree-canvas">
                    <div class="skill-tree-summary">
                        <div class="skill-tree-summary__seal">APT</div>
                        <div class="skill-tree-summary__copy">
                            <p class="skill-tree-summary__kicker">{{ $activeSubject['label'] }}</p>
                            <h2 class="skill-tree-summary__title rs-display">{{ $activeSubject['name'] }}</h2>
                            <p class="skill-tree-summary__meta">
                                {{ $activeTree['unlocked_count'] }}/{{ $activeTree['total_count'] }} compétence(s) débloquée(s)
                            </p>
                            <div class="skill-tree-pill-row skill-tree-pill-row--summary">
                                <span class="skill-tree-pill">{{ $activeTree['name'] }}</span>
                                @if ($activeTree['class_label'])
                                    <span class="skill-tree-pill">{{ $activeTree['class_label'] }}</span>
                                @endif
                                @if ($activeTree['specialization_label'])
                                    <span class="skill-tree-pill">{{ $activeTree['specialization_label'] }}</span>
                                @endif
                                @if ($activeTree['role_label'])
                                    <span class="skill-tree-pill">{{ $activeTree['role_label'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="skill-tree-board" style="--tree-cols: {{ $activeTree['column_count'] }}; --tree-rows: {{ $activeTree['row_count'] }};">
                        <div class="skill-tree-board__field">
                            <svg class="skill-tree-board__lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                @foreach ($activeTree['connections'] as $connection)
                                    <line
                                        class="skill-tree-connection {{ $connection['is_active'] ? 'skill-tree-connection--active' : '' }}"
                                        x1="{{ $connection['source_x'] }}"
                                        y1="{{ $connection['source_y'] }}"
                                        x2="{{ $connection['target_x'] }}"
                                        y2="{{ $connection['target_y'] }}"
                                    />
                                @endforeach
                            </svg>

                            <div class="skill-tree-board__grid">
                                @foreach ($activeTree['nodes'] as $node)
                                    <a
                                        href="{{ route('skills.index', ['subject' => $activeSubject['key'], 'tree' => $activeTree['slug'], 'node' => $node['id']]) }}"
                                        class="skill-tree-node skill-tree-node--{{ $node['state'] }} {{ $selectedNode && $selectedNode['id'] === $node['id'] ? 'skill-tree-node--selected' : '' }}"
                                        style="grid-column: {{ $node['column'] }}; grid-row: {{ $node['row'] }};"
                                        aria-current="{{ $selectedNode && $selectedNode['id'] === $node['id'] ? 'true' : 'false' }}"
                                    >
                                        <span class="skill-tree-node__crest">{{ $node['icon_label'] }}</span>
                                        <span class="skill-tree-node__name">{{ $node['name'] }}</span>
                                        <span class="skill-tree-node__meta">Niv. {{ $node['required_level'] }}</span>
                                        <span class="skill-tree-node__status">
                                            {{ $node['is_unlocked'] ? 'Débloqué' : ($node['state'] === 'available' ? 'Disponible' : 'Verrouillé') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <aside class="skill-tree-detail-panel">
                            @if (session('skill_tree_status'))
                                <div class="skill-tree-status skill-tree-status--success">
                                    {{ session('skill_tree_status') }}
                                </div>
                            @endif

                            @if ($errors->has('skills'))
                                <div class="skill-tree-status skill-tree-status--error {{ session('skill_tree_status') ? 'mt-3' : '' }}">
                                    {{ $errors->first('skills') }}
                                </div>
                            @endif

                            <div class="skill-tree-detail-tools {{ session('skill_tree_status') || $errors->has('skills') ? 'mt-4' : '' }}">
                                <div class="skill-tree-detail-tools__copy">
                                    <p class="skill-tree-section__title">Réinitialisation</p>
                                    <p class="skill-tree-detail-tools__meta">
                                        {{ $activeSubject['free_respecs'] > 0 ? $activeSubject['free_respecs'].' utilisation(s) gratuite(s) restante(s)' : $respecCost.' pièces' }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('skills.respec') }}" class="skill-tree-respec-form">
                                    @csrf
                                    <input type="hidden" name="subject" value="{{ $activeSubject['key'] }}">
                                    <input type="hidden" name="tree" value="{{ $activeTree['slug'] }}">
                                    <button type="submit" class="skill-tree-respec-button">
                                        Respec
                                    </button>
                                </form>
                            </div>

                            @if ($selectedNode)
                                <div class="skill-tree-detail-head mt-4">
                                    <div>
                                        <p class="skill-tree-detail-head__kicker">{{ $selectedNode['slot_label'] }} · {{ $selectedNode['type_label'] }}</p>
                                        <h2 class="skill-tree-detail-name rs-display">{{ $selectedNode['name'] }}</h2>
                                    </div>
                                    <span class="skill-tree-node__crest">{{ $selectedNode['icon_label'] }}</span>
                                </div>

                                <div class="skill-tree-pill-row">
                                    <span class="skill-tree-pill">Niveau {{ $selectedNode['required_level'] }}</span>
                                    @if ($selectedNode['class_label'])
                                        <span class="skill-tree-pill">{{ $selectedNode['class_label'] }}</span>
                                    @endif
                                    @if ($selectedNode['rarity_label'])
                                        <span class="skill-tree-pill">{{ $selectedNode['rarity_label'] }}</span>
                                    @endif
                                    @if ($selectedNode['theme_name'])
                                        <span class="skill-tree-pill">{{ $selectedNode['theme_name'] }}</span>
                                    @endif
                                    @foreach ($selectedNode['tags'] as $tag)
                                        <span class="skill-tree-pill">{{ $tag }}</span>
                                    @endforeach
                                </div>

                                <p class="skill-tree-detail-description">{{ $selectedNode['description'] }}</p>

                                <div class="skill-tree-section">
                                    <p class="skill-tree-section__title">Effets</p>
                                    <div class="skill-tree-list">
                                        @foreach ($selectedNode['effect_lines'] as $line)
                                            <div class="skill-tree-list__item">{{ $line }}</div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="skill-tree-section">
                                    <p class="skill-tree-section__title">Prérequis</p>
                                    <div class="skill-tree-list">
                                        @forelse ($selectedNode['prerequisite_labels'] as $prerequisite)
                                            <div class="skill-tree-list__item">{{ $prerequisite }}</div>
                                        @empty
                                            <div class="skill-tree-list__item">Aucun prérequis.</div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="skill-tree-costs">
                                    <div class="skill-tree-cost">
                                        <p class="skill-tree-cost__label">Points</p>
                                        <p class="skill-tree-cost__value">{{ $selectedNode['unlock_cost'] }}</p>
                                    </div>
                                    <div class="skill-tree-cost">
                                        <p class="skill-tree-cost__label">Spéciaux</p>
                                        <p class="skill-tree-cost__value">{{ $selectedNode['special_cost'] }}</p>
                                    </div>
                                </div>

                                <div class="skill-tree-actions">
                                    @if ($selectedNode['is_unlocked'])
                                        <div class="skill-tree-status skill-tree-status--success">
                                            Cette compétence est déjà débloquée.
                                        </div>
                                    @elseif ($selectedNode['can_unlock'])
                                        <form method="POST" action="{{ route('skills.unlock', $selectedNode['id']) }}">
                                            @csrf
                                            <input type="hidden" name="subject" value="{{ $activeSubject['key'] }}">
                                            <button type="submit" class="skill-tree-unlock-button">
                                                Débloquer
                                            </button>
                                        </form>
                                    @else
                                        <div class="skill-tree-status">
                                            Niveau, prérequis ou points insuffisants pour ce nœud.
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="skill-tree-detail-empty">
                                    <p>Sélectionne un nœud pour afficher son détail.</p>
                                </div>
                            @endif
                        </aside>

                        <div class="skill-tree-footer">
                            <div class="skill-tree-points-core">
                                <div class="skill-tree-points-core__ring">
                                    <span class="skill-tree-points-core__value">{{ $skillPoints }}</span>
                                </div>
                                <p class="skill-tree-points-core__label">Points d’aptitude</p>
                                <div class="skill-tree-points-core__special">
                                    <span>Spéciaux</span>
                                    <strong>{{ $specialSkillPoints }}</strong>
                                </div>
                            </div>

                            <div class="skill-tree-progress">
                                <div class="skill-tree-progress__meta">
                                    <span>Niv. {{ $userLevel }}</span>
                                    <span>{{ $activeTree['unlocked_percent'] }}%</span>
                                </div>
                                <div class="skill-tree-progress__bar">
                                    <span style="width: {{ $activeTree['unlocked_percent'] }}%;"></span>
                                </div>
                                <p class="skill-tree-progress__label">
                                    Réseau maîtrisé à {{ $activeTree['unlocked_percent'] }}%.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="skill-tree-stage rs-panel rs-panel-enter rounded-[2rem]">
                <div class="skill-tree-detail-empty">
                    <p>Aucun arbre de compétences n’est encore configuré.</p>
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
