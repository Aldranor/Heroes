<span class="combat-chip">
    Manche {{ $battleSummary['round'] }}
</span>
@if ($battleSummary['phase_label'])
    <span class="combat-chip combat-chip--rose">
        {{ $battleSummary['phase_label'] }}
    </span>
@endif
