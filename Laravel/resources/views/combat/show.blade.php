<x-layouts::app title="Combat">
    @if ($musicUrl)
        <audio
            id="combat-music"
            src="{{ $musicUrl }}"
            loop
            preload="auto"
            style="display:none"
        ></audio>
    @endif

    <div class="combat-shell flex h-full w-full flex-1 flex-col gap-4" id="combat-app">
        <section class="combat-hero-panel">
            <img src="{{ $context['background'] ?? asset('images/background-combat/battleback9.png') }}" alt="" class="combat-hero-backdrop absolute inset-0 h-full w-full object-cover object-center">
            <div class="combat-hero-overlay absolute inset-0"></div>
            <div class="combat-hero-scanlines"></div>

            <div class="combat-scene relative z-10">
                <div class="combat-encounter-bar">
                    <div class="combat-encounter-copy">
                        <p class="combat-section-title">{{ $context['world_name'] ?? 'Mission' }}</p>
                        <h1 class="combat-encounter-title">{{ $context['node_title'] ?? 'Combat' }}</h1>
                        <p class="combat-encounter-subtitle">{{ $context['map_name'] ?? 'Carte' }} · {{ $context['category_name'] ?? 'Zone' }}</p>
                    </div>

                    <div class="combat-status-strip" id="combat-status-badges">
                        @include('combat.partials.status-badges')
                    </div>
                </div>

                <div class="combat-arena-frame">
                    <div id="combat-stage">
                        @include('combat.partials.stage')
                    </div>

                    <section id="combat-actions-panel" class="combat-bottom-hud">
                        @include('combat.partials.action-panel')
                    </section>
                </div>

                <div id="combat-errors" class="combat-message combat-message--error {{ $errors->any() ? '' : 'hidden' }} mt-4">
                    @if ($errors->any())
                        {{ $errors->first('combat') ?: $errors->first() }}
                    @endif
                </div>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const root = document.getElementById('combat-app');
            if (!root) return;

            const csrfToken = @json(csrf_token());
            const actionUrl = @json(route('combat.action', $battle));
            let pending = false;

            const setError = (message) => {
                const errorNode = document.getElementById('combat-errors');
                if (!errorNode) return;
                if (!message) {
                    errorNode.classList.add('hidden');
                    errorNode.textContent = '';
                    return;
                }
                errorNode.textContent = message;
                errorNode.classList.remove('hidden');
            };

            const applyFragments = (fragments) => {
                const map = {
                    'combat-status-badges': fragments.badges,
                    'combat-stage': fragments.stage,
                    'combat-actions-panel': fragments.actions,
                };

                Object.entries(map).forEach(([id, html]) => {
                    const node = document.getElementById(id);
                    if (node && typeof html === 'string') {
                        node.innerHTML = html;
                    }
                });

                syncCommandMenus();
            };

            const syncCommandMenus = () => {
                root.querySelectorAll('[data-command-menu]').forEach((menuRoot) => {
                    const activeMenu = menuRoot.dataset.commandMenuActive || 'root';
                    menuRoot.querySelectorAll('[data-command-menu-panel]').forEach((panel) => {
                        panel.hidden = panel.dataset.commandMenuPanel !== activeMenu;
                    });
                });
            };

            const setCommandMenu = (menuName = 'root') => {
                const menuRoot = root.querySelector('[data-command-menu]');
                if (!menuRoot) return;
                menuRoot.dataset.commandMenuActive = menuName;
                syncCommandMenus();
            };

            const randomBetween = (min, max) => (Math.random() * (max - min)) + min;
            let spriteAnimatorId = null;

            const hydrateStageDynamics = () => {
                const battlers = root.querySelectorAll('.combat-battler');
                battlers.forEach((node) => {
                    node.style.setProperty('--combat-idle-duration', `${randomBetween(1.95, 3.15).toFixed(2)}s`);
                    node.style.setProperty('--combat-idle-delay', `${randomBetween(-1.5, 0).toFixed(2)}s`);
                    node.style.setProperty('--combat-idle-rise', `${randomBetween(0.6, 1.3).toFixed(2)}%`);
                });
            };

            const startSpriteAnimator = () => {
                if (spriteAnimatorId) {
                    clearInterval(spriteAnimatorId);
                }

                let tick = 0;
                spriteAnimatorId = setInterval(() => {
                    tick += 1;
                    const now = performance.now();
                    const sheets = root.querySelectorAll('.combat-sprite-sheet[data-sprite-sheet="true"]');
                    sheets.forEach((sheet) => {
                        const cols = Math.max(1, Number(sheet.dataset.spriteCols || 1));
                        const baseCol = Math.max(0, Number(sheet.dataset.baseCol || 0));
                        const battler = sheet.closest('.combat-battler');
                        if (!battler) return;

                        if (!sheet.__animationMapParsed) {
                            try {
                                sheet.__animationMap = sheet.dataset.animationMap ? JSON.parse(sheet.dataset.animationMap) : {};
                            } catch (error) {
                                sheet.__animationMap = {};
                            }
                            sheet.__animationMapParsed = true;
                        }

                        // ── Craftpix multi-file sprites ──────────────────────
                        // Each animation state ships as its own PNG (Demon /
                        // Skeleton packs). We pick the right image, swap the
                        // active layer, and animate frames within its grid.
                        if (sheet.dataset.craftpix) {
                            if (!sheet.__craftpixParsed) {
                                try {
                                    sheet.__craftpixStates = JSON.parse(sheet.dataset.craftpix);
                                } catch (error) {
                                    sheet.__craftpixStates = {};
                                }
                                sheet.__craftpixParsed = true;
                            }

                            const cpStates = sheet.__craftpixStates || {};
                            const isRunningCp = battler.classList.contains('combat-battler--running');
                            // Craftpix state priority (matches the in-game
                            // class machine, with a fallback chain to idle).
                            let cpStateKey;
                            if (battler.classList.contains('combat-battler--defeated')) {
                                cpStateKey = cpStates.death ? 'death' : 'idle';
                            } else if (battler.classList.contains('combat-battler--attacking')) {
                                cpStateKey = cpStates.attack ? 'attack' : 'idle';
                            } else if (isRunningCp) {
                                cpStateKey = cpStates.run ? 'run' : (cpStates.walk ? 'walk' : 'idle');
                            } else if (battler.classList.contains('combat-battler--hit')) {
                                cpStateKey = cpStates.hurt ? 'hurt' : 'idle';
                            } else {
                                cpStateKey = 'idle';
                            }
                            const cpState = cpStates[cpStateKey] || cpStates.idle;
                            if (!cpState) return;

                            // Toggle the active <img> layer (CSS handles the
                            // display swap via [data-active]).
                            if (sheet.dataset.craftpixActive !== cpStateKey) {
                                sheet.dataset.craftpixActive = cpStateKey;
                                sheet.querySelectorAll('.combat-sprite-sheet__image--craftpix').forEach((img) => {
                                    if (img.dataset.state === cpStateKey) {
                                        img.dataset.active = 'true';
                                    } else {
                                        img.removeAttribute('data-active');
                                    }
                                });
                                sheet.__cpStateStartedAt = now;
                            }

                            // Compute the current frame within this state's
                            // (cols × rows) grid. Frames flow left-to-right
                            // then wrap to the next row.
                            const cpCols = Math.max(1, Number(cpState.cols || 1));
                            const cpRows = Math.max(1, Number(cpState.rows || 1));
                            const cpTotal = Math.max(1, Number(cpState.frames || cpCols * cpRows));
                            const cpFps = Math.max(1, Number(cpState.fps || 8));
                            const cpFrameDuration = 1000 / cpFps;
                            const cpElapsed = Math.max(0, now - (sheet.__cpStateStartedAt || now));
                            const cpRaw = Math.floor(cpElapsed / cpFrameDuration);
                            const cpFrame = cpState.loop === false
                                ? Math.min(cpTotal - 1, cpRaw)
                                : cpRaw % cpTotal;

                            sheet.style.setProperty('--combat-sprite-cols', String(cpCols));
                            sheet.style.setProperty('--combat-sprite-rows', String(cpRows));
                            sheet.style.setProperty('--combat-sprite-col', String(cpFrame % cpCols));
                            sheet.style.setProperty('--combat-sprite-row', String(Math.floor(cpFrame / cpCols)));
                            return;
                        }

                        if (cols <= 1) return;

                        const isRunning = battler.classList.contains('combat-battler--running');
                        const resolvedState = battler.classList.contains('combat-battler--defeated')
                            ? 'dead'
                            : battler.classList.contains('combat-battler--attacking')
                                ? 'combat_attack'
                                : isRunning
                                    ? 'walk'
                                    : battler.classList.contains('combat-battler--hit')
                                        ? 'hurt'
                                        : 'combat_idle';
                        const animationMap = sheet.__animationMap || {};
                        // For the run animation we deliberately stick to 'walk'
                        // (rows 8-11 of the LPC universal layout) rather than
                        // the extended 'run' strip (rows 38-41): equipment
                        // sheets are usually universal-only and would render
                        // an empty row past 20, making clothes disappear while
                        // the hero charges. Walk is supported by every layer.
                        const stateConfig = animationMap[resolvedState]
                            || animationMap.combat_idle
                            || animationMap.idle
                            || null;

                        if (stateConfig) {
                            const stateKey = `${resolvedState}:${stateConfig.row}:${stateConfig.start_column}`;
                            if (sheet.__spriteStateKey !== stateKey) {
                                sheet.__spriteStateKey = stateKey;
                                sheet.__spriteStateStartedAt = now;
                            }

                            const sequence = Array.isArray(stateConfig.sequence) && stateConfig.sequence.length > 0
                                ? stateConfig.sequence.map((frame) => Number(frame) || 0)
                                : [0];
                            const frameDuration = 1000 / Math.max(1, Number(stateConfig.fps || 8));
                            const elapsed = Math.max(0, now - (sheet.__spriteStateStartedAt || now));
                            const rawIndex = Math.floor(elapsed / frameDuration);
                            const sequenceIndex = stateConfig.loop === false
                                ? Math.min(sequence.length - 1, rawIndex)
                                : rawIndex % sequence.length;
                            const mappedFrame = Math.min(
                                cols - 1,
                                Math.max(0, Number(stateConfig.start_column || 0) + Number(sequence[sequenceIndex] || 0))
                            );

                            sheet.style.setProperty('--combat-sprite-row', String(Number(stateConfig.row || 0)));
                            sheet.style.setProperty('--combat-sprite-col', String(mappedFrame));
                            return;
                        }

                        let cycle = [baseCol];
                        if (battler.classList.contains('combat-battler--attacking')) {
                            cycle = [baseCol, Math.min(cols - 1, baseCol + 1), Math.min(cols - 1, baseCol + 2), Math.min(cols - 1, baseCol + 1)];
                        } else if (battler.classList.contains('combat-battler--running')) {
                            // Faster walk cycle: cycles all available cols quickly
                            cycle = [];
                            for (let f = 0; f < Math.min(cols, 4); f++) cycle.push(f);
                        } else if (battler.classList.contains('combat-battler--hit')) {
                            cycle = [baseCol, Math.min(cols - 1, baseCol + 1)];
                        } else if (battler.classList.contains('combat-battler--current')) {
                            cycle = [baseCol, Math.min(cols - 1, baseCol + 1), baseCol];
                        } else {
                            cycle = [baseCol, Math.min(cols - 1, baseCol + 1)];
                        }

                        const frame = cycle[tick % cycle.length] ?? baseCol;
                        sheet.style.setProperty('--combat-sprite-col', String(frame));
                    });
                }, 130);
            };

            const spawnFloatText = (targetNode, amount, variant, isCrit = false) => {
                if (!targetNode || !amount || amount <= 0) return;
                const rect = targetNode.getBoundingClientRect();
                const text = document.createElement('div');
                text.className = `combat-damage-float combat-damage-float--${variant}${isCrit ? ' combat-damage-float--crit' : ''}`;
                text.textContent = `${variant === 'heal' ? '+' : '-'}${amount}${isCrit ? ' CRIT' : ''}`;
                text.style.left = `${rect.left + (rect.width / 2) + randomBetween(-12, 12)}px`;
                text.style.top = `${rect.top + (rect.height * 0.28)}px`;
                text.style.fontSize = `${isCrit ? 22 : 18}px`;
                document.body.appendChild(text);

                text.animate([
                    { opacity: 0, transform: 'translate(-50%, -20%) scale(0.76)' },
                    { opacity: 1, transform: 'translate(-50%, -50%) scale(1.06)', offset: 0.25 },
                    { opacity: 0, transform: `translate(-50%, -175%) scale(0.94)` }
                ], {
                    duration: 760 + Math.floor(randomBetween(0, 180)),
                    easing: 'cubic-bezier(0.18, 0.82, 0.28, 1)',
                }).finished.finally(() => text.remove());
            };

            const shakeStage = () => {
                const stage = document.getElementById('combat-stage');
                if (!stage) return;
                stage.animate([
                    { transform: 'translateX(0px)' },
                    { transform: `translateX(${randomBetween(-6, -3).toFixed(1)}px)` },
                    { transform: `translateX(${randomBetween(3, 7).toFixed(1)}px)` },
                    { transform: 'translateX(0px)' },
                ], {
                    duration: 180 + Math.floor(randomBetween(0, 90)),
                    easing: 'ease-out',
                });
            };

            const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

            // Pixel offset to leave between the attacker and the target so the
            // sprites don't visually overlap at impact.
            const STRIKE_GAP = 24;

            const playRunAttack = async (lastAction) => {
                const actorKey = lastAction?.actor_key;
                const targetKey = lastAction?.target_key;
                if (!actorKey || !targetKey) return;

                const actorNode = document.querySelector(`[data-actor-key="${actorKey}"]`);
                const targetNode = document.querySelector(`[data-actor-key="${targetKey}"]`);
                if (!actorNode || !targetNode) return;
                if (actorNode.classList.contains('combat-battler--defeated')) return;

                const isSelfTarget = actorKey === targetKey;
                const actorRect = actorNode.getBoundingClientRect();
                const targetRect = targetNode.getBoundingClientRect();
                const actorCenterX = actorRect.left + (actorRect.width / 2);
                const actorCenterY = actorRect.top + (actorRect.height / 2);
                const targetCenterX = targetRect.left + (targetRect.width / 2);
                const targetCenterY = targetRect.top + (targetRect.height / 2);
                const rawDx = targetCenterX - actorCenterX;
                const rawDy = targetCenterY - actorCenterY;
                const directionSign = rawDx >= 0 ? 1 : -1;
                // Stop a bit before the target so we don't overlap.
                const stopGap = isSelfTarget ? 0 : (targetRect.width / 2) + STRIKE_GAP;
                const dx = isSelfTarget ? 0 : rawDx - (directionSign * stopGap);
                const dy = isSelfTarget ? 0 : rawDy;

                const baseTransform = 'translate(-50%, -50%)';
                const reachTransform = `translate(calc(-50% + ${dx.toFixed(1)}px), calc(-50% + ${dy.toFixed(1)}px))`;
                // Slightly elevate at the apex of the run to suggest weight & momentum.
                const apexLift = Math.min(18, Math.max(6, Math.abs(rawDx) * 0.04));
                const apexTransform = `translate(calc(-50% + ${(dx * 0.55).toFixed(1)}px), calc(-50% + ${(dy * 0.55 - apexLift).toFixed(1)}px))`;

                const distance = Math.hypot(rawDx, rawDy);
                // Faster runs for short distances, but cap so cross-stage charges
                // still feel snappy.
                const runOutDuration = isSelfTarget ? 0 : Math.min(520, Math.max(220, distance * 0.85));
                const runBackDuration = isSelfTarget ? 0 : Math.min(440, Math.max(180, distance * 0.6));

                // The actor is going to be temporarily lifted above other battlers
                // during the strike so the layering reads correctly.
                const previousZIndex = actorNode.style.zIndex;
                actorNode.style.zIndex = '60';

                const cleanup = () => {
                    actorNode.classList.remove('combat-battler--running');
                    actorNode.classList.remove('combat-battler--running-back');
                    actorNode.classList.remove('combat-battler--attacking');
                    actorNode.style.transform = '';
                    actorNode.style.zIndex = previousZIndex;
                };

                try {

                // Phase 1 – run toward the target (skipped if self-targeted).
                if (!isSelfTarget) {
                    actorNode.classList.add('combat-battler--running');
                    const runOut = actorNode.animate([
                        { transform: baseTransform },
                        { transform: apexTransform, offset: 0.55 },
                        { transform: reachTransform },
                    ], {
                        duration: runOutDuration,
                        easing: 'cubic-bezier(0.32, 0.72, 0.4, 1)',
                        fill: 'forwards',
                    });
                    await runOut.finished;
                    runOut.commitStyles?.();
                    runOut.cancel();
                    // Manually keep the actor pinned at the strike position
                    // while we play the slash.
                    actorNode.style.transform = reachTransform;
                    // NOTE: we deliberately keep the `running` class during
                    // the strike too — the animator gives priority to
                    // `attacking` for the sprite frames, but CSS uses
                    // `:not(.combat-battler--running)` on its `combatStep`
                    // keyframe to suppress its own transform animation,
                    // letting our JS lunge below run uncontested.
                }

                // Phase 2 – attack (slash / cast pose at target).
                actorNode.classList.add('combat-battler--attacking');

                if (!isSelfTarget) {
                    // Slight forward lunge at the moment of impact. Skipped
                    // for self-targeted skills where the CSS `combatStep`
                    // keyframe handles the bounce on its own.
                    const lungeTransform = `translate(calc(-50% + ${(dx + (directionSign * 6)).toFixed(1)}px), calc(-50% + ${dy.toFixed(1)}px))`;
                    actorNode.animate([
                        { transform: reachTransform },
                        { transform: lungeTransform, offset: 0.4 },
                        { transform: reachTransform },
                    ], {
                        duration: 320,
                        easing: 'ease-out',
                    });
                }

                // Hit feedback on the target, fired right at the impact frame.
                await sleep(120);
                const damage = lastAction?.damage || 0;
                const healing = lastAction?.healing || 0;
                if (!isSelfTarget && damage > 0) {
                    targetNode.animate([
                        { filter: 'brightness(1)' },
                        { filter: `brightness(${randomBetween(1.32, 1.62).toFixed(2)})` },
                        { filter: 'brightness(1)' },
                    ], { duration: 200, easing: 'ease-out' });
                    targetNode.animate([
                        { transform: 'translate(-50%, -50%)' },
                        { transform: `translate(calc(-50% + ${randomBetween(-9, -4).toFixed(1)}px), calc(-50% + ${randomBetween(-2, 2).toFixed(1)}px))` },
                        { transform: `translate(calc(-50% + ${randomBetween(4, 9).toFixed(1)}px), calc(-50% + ${randomBetween(-2, 2).toFixed(1)}px))` },
                        { transform: 'translate(-50%, -50%)' },
                    ], { duration: 220, easing: 'ease-out' });
                    shakeStage();
                } else if (healing > 0) {
                    // Soft green pulse for heals — no shake.
                    targetNode.animate([
                        { filter: 'brightness(1) drop-shadow(0 0 0 rgba(74, 222, 128, 0))' },
                        { filter: 'brightness(1.2) drop-shadow(0 0 12px rgba(74, 222, 128, 0.85))' },
                        { filter: 'brightness(1) drop-shadow(0 0 0 rgba(74, 222, 128, 0))' },
                    ], { duration: 460, easing: 'ease-out' });
                }
                await sleep(200);

                actorNode.classList.remove('combat-battler--attacking');

                // Phase 3 – run back to the original position (mirror class
                // flips the sprite so the actor faces the way they're moving).
                if (!isSelfTarget) {
                    // `running` was kept on through the strike – just add the
                    // mirror class for the return leg.
                    actorNode.classList.add('combat-battler--running-back');
                    const runBack = actorNode.animate([
                        { transform: reachTransform },
                        { transform: apexTransform, offset: 0.5 },
                        { transform: baseTransform },
                    ], {
                        duration: runBackDuration,
                        easing: 'cubic-bezier(0.4, 0, 0.4, 1)',
                        fill: 'forwards',
                    });
                    await runBack.finished;
                    runBack.cancel();
                }

                } finally {
                    // Always reset classes and inline styles so the next CSS
                    // animation (idle) takes over – even if an animation was
                    // interrupted by an error mid-flight.
                    cleanup();
                }
            };

            // ── Target selection mode ──────────────────────────────────────────
            const enterTargetMode = (form, targets) => {
                root.classList.add('combat--targeting');
                root.__targetForm = form;

                const stage = document.getElementById('combat-stage');

                targets.forEach(node => {
                    node.classList.add('combat-battler--targetable');
                    node.addEventListener('click', onTargetClick, { once: true });
                });

                const prompt = document.createElement('div');
                prompt.className = 'combat-target-prompt';
                prompt.textContent = 'Cliquez sur un ennemi pour cibler';
                prompt.id = 'combat-target-prompt';
                stage.appendChild(prompt);

                const cancelOnEscape = (e) => {
                    if (e.key === 'Escape') {
                        cleanupTargetMode();
                    }
                };
                document.addEventListener('keydown', cancelOnEscape);
                root.__targetCancelEscape = cancelOnEscape;

                setTimeout(() => {
                    const cancelOnClick = (e) => {
                        if (!e.target.closest('.combat-battler--targetable')) {
                            cleanupTargetMode();
                        }
                    };
                    document.addEventListener('click', cancelOnClick);
                    root.__targetCancelClick = cancelOnClick;
                }, 100);
            };

            const cleanupTargetMode = () => {
                root.classList.remove('combat--targeting');
                root.__targetForm = null;

                const prompt = document.getElementById('combat-target-prompt');
                if (prompt) prompt.remove();

                if (root.__targetCancelEscape) {
                    document.removeEventListener('keydown', root.__targetCancelEscape);
                    root.__targetCancelEscape = null;
                }
                if (root.__targetCancelClick) {
                    document.removeEventListener('click', root.__targetCancelClick);
                    root.__targetCancelClick = null;
                }
            };

            const onTargetClick = (event) => {
                const node = event.currentTarget;
                const form = root.__targetForm;
                if (!form || pending) return;

                form.querySelector('input[name="target"]').value = node.dataset.actorKey;
                form.dataset.targetReady = '1';
                cleanupTargetMode();
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            };

            root.addEventListener('submit', async (event) => {
                const form = event.target.closest('form[data-combat-action-form]');
                if (!form || pending) return;

                const skillTarget = form.dataset.skillTarget;
                if (skillTarget === 'enemy' && !form.dataset.targetReady) {
                    const targets = root.querySelectorAll('.combat-battler--enemy:not(.combat-battler--defeated)');
                    if (targets.length > 1) {
                        event.preventDefault();
                        enterTargetMode(form, targets);
                        return;
                    }
                }

                event.preventDefault();
                pending = true;
                setError('');

                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
                submitButtons.forEach((button) => {
                    button.disabled = true;
                    button.classList.add('opacity-60');
                });

                try {
                    const formData = new FormData(form);
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        setError(payload.message || 'Action invalide.');
                        return;
                    }

                    // Snapshot which enemies are already defeated BEFORE we
                    // replay anything. After fragments re-render, any enemy
                    // that's defeated and wasn't in this set is "freshly
                    // killed" – only those should play the death animation.
                    // Without this, every dead enemy would re-play their
                    // fade-out on each subsequent kill (DOM nodes are
                    // recreated on every applyFragments).
                    const previouslyDeadEnemies = new Set(
                        Array.from(root.querySelectorAll('.combat-battler--enemy.combat-battler--defeated'))
                            .map((node) => node.getAttribute('data-actor-key'))
                            .filter(Boolean)
                    );

                    // Replay every action that ran during this submit cycle:
                    // the player's turn first, then any auto-advanced enemy
                    // turns, in order. Falls back to last_action if the
                    // server didn't expose the replay list.
                    const replay = Array.isArray(payload.turn_actions) && payload.turn_actions.length > 0
                        ? payload.turn_actions
                        : (payload.last_action ? [payload.last_action] : []);

                    for (const action of replay) {
                        await playRunAttack(action);
                        const targetNode = action?.target_key
                            ? document.querySelector(`[data-actor-key="${action.target_key}"]`)
                            : null;
                        if ((action?.damage || 0) > 0) {
                            spawnFloatText(targetNode, action.damage, 'damage', !!action.critical);
                        } else if ((action?.healing || 0) > 0) {
                            spawnFloatText(targetNode, action.healing, 'heal', false);
                        }
                        // Tiny breath between two consecutive strikes so they
                        // don't blur into one another.
                        await sleep(80);
                    }

                    applyFragments(payload.fragments || {});

                    // Tag the freshly-killed enemies so only they play the
                    // death animation – the already-dead ones stay silently
                    // invisible (CSS sets opacity 0 by default).
                    root.querySelectorAll('.combat-battler--enemy.combat-battler--defeated').forEach((node) => {
                        const key = node.getAttribute('data-actor-key');
                        if (key && !previouslyDeadEnemies.has(key)) {
                            node.classList.add('combat-battler--just-defeated');
                        }
                    });

                    hydrateStageDynamics();
                    startSpriteAnimator();
                } catch (error) {
                    setError('Erreur réseau pendant le tour. Réessaie.');
                } finally {
                    pending = false;
                    if (form) {
                        delete form.dataset.targetReady;
                        const ti = form.querySelector('input[name="target"]');
                        if (ti) ti.value = '';
                    }
                    submitButtons.forEach((button) => {
                        button.disabled = false;
                        button.classList.remove('opacity-60');
                    });
                }
            });

            root.addEventListener('click', (event) => {
                const openMenuButton = event.target.closest('[data-command-menu-open]');
                if (openMenuButton) {
                    event.preventDefault();
                    setCommandMenu(openMenuButton.dataset.commandMenuOpen || 'root');
                    return;
                }

                const backMenuButton = event.target.closest('[data-command-menu-back]');
                if (backMenuButton) {
                    event.preventDefault();
                    setCommandMenu(backMenuButton.dataset.commandMenuBack || 'root');
                }
            });

            hydrateStageDynamics();
            syncCommandMenus();
            startSpriteAnimator();

            // ── Combat music ─────────────────────────────────────────────────
            // Browsers block autoplay unconditionally; we need a real user gesture.
            // Strategy: start on the very first pointerdown anywhere in the app,
            // then let the toggle button mute/unmute from that point on.
            const music = document.getElementById('combat-music');
            if (music) {
                let musicUnlocked = false;

                const fadeIn = () => {
                    music.volume = 0;
                    let v = 0;
                    const fade = setInterval(() => {
                        v = Math.min(1, v + 0.04);
                        music.volume = v * 0.55;
                        if (v >= 1) clearInterval(fade);
                    }, 50);
                };

                const unlockMusic = () => {
                    if (musicUnlocked) return;
                    musicUnlocked = true;
                    root.removeEventListener('pointerdown', unlockMusic);
                    music.play().then(fadeIn).catch(() => {});
                    toggleBtn.style.opacity = '1';
                    toggleBtn.title = 'Couper la musique';
                };

                // Toggle button – visible dès le départ, invite implicite à cliquer
                const toggleBtn = document.createElement('button');
                toggleBtn.id = 'combat-music-toggle';
                toggleBtn.title = 'Lancer la musique';
                toggleBtn.innerHTML = '🎵';
                toggleBtn.style.cssText = [
                    'position:fixed', 'bottom:1rem', 'right:1rem', 'z-index:999',
                    'width:2.25rem', 'height:2.25rem', 'border-radius:50%',
                    'background:rgba(0,0,0,0.55)', 'border:1px solid rgba(255,255,255,0.18)',
                    'color:#fff', 'font-size:1rem', 'cursor:pointer',
                    'display:flex', 'align-items:center', 'justify-content:center',
                    'backdrop-filter:blur(4px)', 'transition:opacity 0.2s',
                    'opacity:0.45',
                ].join(';');
                document.body.appendChild(toggleBtn);

                // First interaction anywhere in the combat shell starts the music
                root.addEventListener('pointerdown', unlockMusic);

                toggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (!musicUnlocked) {
                        unlockMusic();
                        return;
                    }
                    if (music.paused) {
                        music.play().then(fadeIn).catch(() => {});
                        toggleBtn.style.opacity = '1';
                        toggleBtn.title = 'Couper la musique';
                    } else {
                        music.pause();
                        toggleBtn.style.opacity = '0.45';
                        toggleBtn.title = 'Lancer la musique';
                    }
                });
            }
        })();
    </script>
</x-layouts::app>
