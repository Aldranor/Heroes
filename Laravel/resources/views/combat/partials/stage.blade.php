<div class="combat-stage">
    <div class="combat-stage-floor"></div>

    @foreach ($playerBattlers as $battler)
        <x-combat.battler :battler="$battler" />
    @endforeach

    @foreach ($enemyBattlers as $battler)
        <x-combat.battler :battler="$battler" />
    @endforeach
</div>
