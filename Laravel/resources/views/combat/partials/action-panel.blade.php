@if ($battle->status->value === 'ongoing')
    <div class="combat-window combat-hud">
        <div class="combat-hud__layout">
            <section data-command-menu data-command-menu-active="root">
                <div class="combat-command-pane__status">
                    <div class="combat-command-pane__notice">
                        <span class="combat-command-pane__message">{{ $latestLog['text'] ?? 'Choisis une action.' }}</span>
                    </div>
                </div>

                <div class="combat-command-menus">
                    <div data-command-menu-panel="root">
                        <div class="combat-command-list combat-command-list--root">
                            @forelse (($commandMenus['root'] ?? []) as $entry)
                                @if (($entry['kind'] ?? null) === 'submenu')
                                    <button
                                        type="button"
                                        class="combat-command-btn combat-command-btn--menu"
                                        data-command-menu-open="{{ $entry['menu'] }}"
                                    >
                                        <span class="combat-command-btn__name">{{ $entry['label'] }}</span>
                                        <span class="combat-command-btn__meta">{{ $entry['meta'] }}</span>
                                    </button>
                                @elseif (($entry['kind'] ?? null) === 'skill' && is_array($entry['skill'] ?? null))
                                    <form method="POST" action="{{ route('combat.action', $battle) }}"
                                          data-combat-action-form
                                          data-skill-target="{{ $entry['skill']['target_type'] }}">
                                        @csrf
                                        <input type="hidden" name="skill" value="{{ $entry['skill']['slug'] }}">
                                        <input type="hidden" name="target" value="">

                                        <button type="submit" class="combat-command-btn combat-command-btn--menu">
                                            <span class="combat-command-btn__name">{{ $entry['label'] }}</span>
                                            <span class="combat-command-btn__meta">{{ $entry['meta'] }}</span>
                                        </button>
                                    </form>
                                @endif
                            @empty
                                <div class="combat-command-empty">
                                    Aucune action disponible.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="combat-command-panel" data-command-menu-panel="skills" hidden>
                        <div class="combat-command-subhead">
                            <button type="button" class="combat-command-back" data-command-menu-back="root">
                                Retour
                            </button>
                            <span class="combat-command-subtitle">Compétences</span>
                        </div>

                        <div class="combat-command-list combat-command-list--skills">
                            @forelse (($commandMenus['skills'] ?? []) as $skill)
                                <form method="POST" action="{{ route('combat.action', $battle) }}"
                                      data-combat-action-form
                                      data-skill-target="{{ $skill['target_type'] }}">
                                    @csrf
                                    <input type="hidden" name="skill" value="{{ $skill['slug'] }}">
                                    <input type="hidden" name="target" value="">

                                    <button type="submit" class="combat-command-btn">
                                        <span class="combat-command-btn__name">{{ $skill['label'] }}</span>
                                        <span class="combat-command-btn__meta">
                                            {{ $skill['mana_cost'] }} MP · {{ $skill['target_label'] }} · CD {{ $skill['cooldown'] }}
                                        </span>
                                    </button>
                                </form>
                            @empty
                                <div class="combat-command-empty">
                                    Aucune compétence disponible.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section aria-label="État du groupe">
                <div class="combat-party-list">
                    @foreach ($partyRows as $row)
                        <div class="{{ $row['classes'] }}">
                            <div class="combat-party-row__main">
                                <div class="combat-party-row__head">
                                    <div>
                                        <p class="combat-party-row__name">{{ $row['name'] }}</p>
                                        <p class="combat-party-row__meta">Lv. {{ $row['level'] }}</p>
                                    </div>
                                    <div class="combat-party-row__state">
                                        {{ $row['state_label'] }}
                                    </div>
                                </div>

                                <div class="combat-party-row__resource">
                                    <span class="combat-party-row__label">HP</span>
                                    <div class="combat-hud-bar">
                                        <span class="combat-hud-bar__fill combat-hud-bar__fill--hp" style="width: {{ $row['hp_percent'] }}%;"></span>
                                    </div>
                                    <span class="combat-party-row__value">{{ $row['current_hp'] }}/{{ $row['max_hp'] }}</span>
                                </div>

                                <div class="combat-party-row__resource">
                                    <span class="combat-party-row__label">MP</span>
                                    <div class="combat-hud-bar">
                                        <span class="combat-hud-bar__fill combat-hud-bar__fill--mp" style="width: {{ $row['mana_percent'] }}%;"></span>
                                    </div>
                                    <span class="combat-party-row__value">{{ $row['current_mana'] }}/{{ $row['max_mana'] }}</span>
                                </div>
                            </div>

                            <div class="combat-party-row__portrait" aria-hidden="true">
                                @if (($row['portrait']['type'] ?? null) === 'sheet')
                                    <x-combat.sprite-sheet
                                        class="{{ $row['portrait']['class'] }}"
                                        :payload="$row['portrait']['payload']"
                                        :animated="false"
                                    />
                                @elseif (($row['portrait']['type'] ?? null) === 'image')
                                    <img
                                        class="combat-party-portrait__image"
                                        src="{{ $row['portrait']['src'] }}"
                                        alt=""
                                        draggable="false"
                                        loading="lazy"
                                        decoding="async"
                                        style="{{ $row['portrait']['style'] }}"
                                    >
                                @else
                                    <div class="{{ $row['portrait']['class'] }}">{{ $row['portrait']['label'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@else
    <div class="combat-window combat-hud">
        <div class="combat-hud__notice">
            <span class="combat-hud__turn">{{ $battleOutcome['title'] }}</span>
            <span class="combat-hud__message">{{ $battleOutcome['message'] }}</span>
        </div>

        <div class="combat-result-grid mt-4">
            <div class="combat-result-card">
                <p class="combat-detail-title">Pièces</p>
                <p class="mt-3 text-2xl font-semibold text-stone-50">+{{ $rewards['coins'] ?? 0 }}</p>
            </div>
        </div>

        <a href="{{ $backRoute }}" class="combat-back-button mt-4">
            <span aria-hidden="true">←</span>
            <span>Retour à la carte</span>
        </a>
    </div>
@endif
