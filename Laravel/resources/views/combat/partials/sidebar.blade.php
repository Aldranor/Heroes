<div class="combat-window grid gap-4">
    <div class="combat-subwindow">
        <p class="combat-detail-title">Statut</p>

        <div class="combat-sidebar-grid mt-3">
            <div class="combat-stat-card">
                <p class="combat-stat-card__label">HP</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['hp'] }}</p>
            </div>

            <div class="combat-stat-card">
                <p class="combat-stat-card__label">MP</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['mp'] }}</p>
            </div>

            <div class="combat-stat-card">
                <p class="combat-stat-card__label">Crit</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['crit'] }}</p>
            </div>

            <div class="combat-stat-card">
                <p class="combat-stat-card__label">Précision</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['precision'] }}</p>
            </div>

            <div class="combat-stat-card">
                <p class="combat-stat-card__label">Maîtrise</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['mastery'] }}</p>
            </div>

            <div class="combat-stat-card">
                <p class="combat-stat-card__label">Bonus dmg</p>
                <p class="combat-stat-card__value">{{ $currentActorStats['damage_bonus'] }}</p>
            </div>
        </div>
    </div>

    <div class="combat-subwindow">
        <p class="combat-detail-title">Journal</p>

        <div class="combat-log-list mt-3">
            @foreach ($sidebarLogs as $entry)
                <div class="combat-log-entry {{ $entry['tone_class'] }}">
                    {{ $entry['text'] }}
                </div>
            @endforeach
        </div>
    </div>
</div>
