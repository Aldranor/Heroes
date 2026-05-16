const animateAvatarStage = (stage) => {
    if (!stage) {
        return;
    }

    stage.classList.remove('is-animating');
    void stage.offsetWidth;
    stage.classList.add('is-animating');
};

const hexToRgb = (hex) => {
    const normalized = (hex || '').replace('#', '');

    if (normalized.length !== 6) {
        return null;
    }

    return {
        red: Number.parseInt(normalized.slice(0, 2), 16),
        green: Number.parseInt(normalized.slice(2, 4), 16),
        blue: Number.parseInt(normalized.slice(4, 6), 16),
    };
};

const mixHex = (source, target, ratio) => {
    const sourceRgb = hexToRgb(source);
    const targetRgb = hexToRgb(target);

    if (!sourceRgb || !targetRgb) {
        return source;
    }

    const mixChannel = (from, to) => Math.round(from + ((to - from) * ratio));

    return `#${[
        mixChannel(sourceRgb.red, targetRgb.red),
        mixChannel(sourceRgb.green, targetRgb.green),
        mixChannel(sourceRgb.blue, targetRgb.blue),
    ].map((value) => value.toString(16).padStart(2, '0')).join('').toUpperCase()}`;
};

const buildSkinPalette = (hex) => ({
    '--avatar-skin-base': hex,
    '--avatar-skin-light': mixHex(hex, '#FFE7D3', 0.34),
    '--avatar-skin-dark': mixHex(hex, '#8C5437', 0.28),
    '--avatar-skin-deep': mixHex(hex, '#532B1C', 0.48),
    '--avatar-skin-shadow': mixHex(hex, '#6E3926', 0.38),
    '--avatar-outline': mixHex(hex, '#6E3926', 0.38),
    '--avatar-eye': '#171717',
    '--avatar-eye-light': '#FFFFFF',
});

const buildHairPalette = (hex) => ({
    '--avatar-hair-base': hex,
    '--avatar-hair-light': mixHex(hex, '#EBC99C', 0.20),
    '--avatar-hair-dark': mixHex(hex, '#42241A', 0.32),
    '--avatar-hair-deep': mixHex(hex, '#21110C', 0.54),
    '--avatar-hair-detail': mixHex(hex, '#F6E7C0', 0.55),
});

const buildOutfitPalette = (accent) => ({
    '--avatar-outfit-base': accent,
    '--avatar-outfit-light': mixHex(accent, '#F6E7C0', 0.26),
    '--avatar-outfit-dark': mixHex(accent, '#1F2937', 0.34),
    '--avatar-outfit-deep': mixHex(accent, '#111827', 0.52),
    '--avatar-outfit-detail': mixHex(accent, '#FFF4D4', 0.68),
    '--avatar-stage-aura': accent,
});

const escapeHtml = (value) => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const parseJsonScript = (element) => {
    if (!element) {
        return {};
    }

    try {
        return JSON.parse(element.textContent || '{}');
    } catch {
        return {};
    }
};

const renderSheetLayerHtml = (asset, className, loading = 'lazy') => {
    if (!asset?.src || !asset?.image_style) {
        return '';
    }

    const fetchPriority = loading === 'eager' ? 'high' : 'low';

    return `
        <div class="${escapeHtml(className)}" aria-hidden="true">
            <img
                class="ob-avatar-sprite__image"
                src="${escapeHtml(asset.src)}"
                alt=""
                style="${escapeHtml(asset.image_style)}"
                loading="${escapeHtml(loading)}"
                decoding="async"
                fetchpriority="${escapeHtml(fetchPriority)}"
                draggable="false"
            >
        </div>
    `.trim();
};

const initOnboarding = () => {
    document.querySelectorAll('[data-onboarding-root]').forEach((root) => {
        const totalSteps = Number(root.dataset.totalSteps || 3);
        const defaultBodyKey = root.dataset.defaultBodyKey || 'human-female';
        const defaultHairKey = root.dataset.defaultHairKey || 'bob--female--black';
        const defaultHairModel = root.dataset.defaultHairModel || 'bob';
        const defaultOutfitPresetKey = root.dataset.defaultOutfitPresetKey || '';
        const defaultOutfitAccent = root.dataset.defaultOutfitAccent || '#5B6CFF';
        const defaultBodyProfile = root.dataset.defaultBodyProfile || 'female';
        const defaultSkin = root.dataset.defaultSkin || '#F7D7C4';
        const defaultHairScale = root.dataset.defaultHairScale || '1.00';
        const defaultHairOffsetY = root.dataset.defaultHairOffsetY || '0%';
        const defaultHairImageStyle = root.dataset.defaultHairImageStyle || '';
        const panels = Array.from(root.querySelectorAll('[data-onboarding-panel]'));
        const stepIndicators = Array.from(root.querySelectorAll('[data-step-indicator]'));
        const nicknameInput = root.querySelector('[data-avatar-field="nickname"]');
        const avatarStage = root.querySelector('[data-avatar-stage]');
        const avatarLayer = root.querySelector('[data-avatar-layer]');
        const avatarNickname = root.querySelector('[data-avatar-nickname]');
        const avatarClass = root.querySelector('[data-avatar-class]');
        const summaryNickname = root.querySelector('[data-summary-nickname]');
        const summaryClass = root.querySelector('[data-summary-class]');
        const progressLabel = root.querySelector('[data-progress-label]');
        const stepName = root.querySelector('[data-step-name]');
        const buttonsNeedingStepOne = root.querySelectorAll('[data-requires="step-1"]');
        const buttonsNeedingStepTwo = root.querySelectorAll('[data-requires="step-2"]');
        const submitButton = root.querySelector('[data-submit-onboarding]');
        const summaryCompanions = root.querySelectorAll('[data-summary-companion]');
        const classAvatarPreviews = root.querySelectorAll('[data-class-avatar-preview]');
        const hairModelCards = Array.from(root.querySelectorAll('[data-hair-model-card]'));
        const hairVariantPanels = Array.from(root.querySelectorAll('[data-hair-variant-panel]'));
        const activeHairStyleLabel = root.querySelector('[data-active-hair-style]');
        const hairVariantManifest = parseJsonScript(root.querySelector('[data-hair-variant-manifest]'));
        const hairVariantsUrl = root.dataset.hairVariantsUrl || '';
        const bodyVariantManifest = parseJsonScript(root.querySelector('[data-body-variant-manifest]'));
        const bodyVariantSection = root.querySelector('[data-body-variant-section]');
        const bodyVariantPanels = Array.from(root.querySelectorAll('[data-body-variant-panel]'));
        const activeBodyVariantLabel = root.querySelector('[data-active-body-variant]');
        const sliders = Array.from(root.querySelectorAll('[data-slider]'));

        let currentBodyVariant = {};

        let currentStep = 1;
        const hairAssetManifest = {};
        const loadedHairModels = new Set(Object.keys(hairVariantManifest || {}));
        const pendingHairVariantRequests = {};

        const registerHairAssets = (model) => {
            Object.entries(model?.profiles || {}).forEach(([, variants]) => {
                (variants || []).forEach((variant) => {
                    if (!variant?.key || !variant?.src) {
                        return;
                    }

                    hairAssetManifest[variant.key] = {
                        src: variant.src,
                        image_style: variant.image_style || defaultHairImageStyle,
                    };
                });
            });
        };

        Object.values(hairVariantManifest || {}).forEach(registerHairAssets);

        const getChecked = (name) => root.querySelector(`[name="${name}"]:checked`);
        const getHairInputs = () => Array.from(root.querySelectorAll('[name="hair_asset"]'));
        const getTemplateHtml = (type, key) => root.querySelector(`template[data-avatar-template="${type}"][data-avatar-key="${key}"]`)?.innerHTML.trim() || '';
        const getHairTemplateHtml = (key, loading = 'lazy') => renderSheetLayerHtml(
            hairAssetManifest?.[key],
            'ob-avatar-asset ob-avatar-svg ob-avatar-svg--hair',
            loading,
        );
        const getOutfitTemplateKey = (presetKey, bodyKey) => `${presetKey}::${bodyKey}`;
        const getCurrentBodyProfile = () => getChecked('body_asset')?.dataset.avatarProfile || defaultBodyProfile;
        const getHairInputByValue = (value) => getHairInputs().find((input) => input.value === value) || null;
        const getHairInputsForModel = (modelKey) => getHairInputs().filter((input) => input.dataset.hairModel === modelKey);
        const getHairInputsForModelAndProfile = (modelKey, profile) => getHairInputs().filter((input) => input.dataset.hairModel === modelKey && input.dataset.hairProfile === profile);
        const supportsProfile = (card, profile) => (card.dataset.hairSupportedProfiles || '').split(',').filter(Boolean).includes(profile);
        const getDefaultVariantForProfile = (card, profile) => {
            const datasetKey = `hairDefaultVariant${profile.charAt(0).toUpperCase()}${profile.slice(1)}`;
            return card.dataset[datasetKey] || card.dataset.hairDefaultVariant || '';
        };
        const getDefaultAssetForProfile = (card, profile) => {
            const datasetKey = `hairDefaultSrc${profile.charAt(0).toUpperCase()}${profile.slice(1)}`;
            const src = card.dataset[datasetKey] || '';

            if (!src) {
                return null;
            }

            return {
                src,
                image_style: defaultHairImageStyle,
            };
        };
        const loadHairVariants = async (modelKey) => {
            if (!modelKey || loadedHairModels.has(modelKey)) {
                return true;
            }

            if (!hairVariantsUrl) {
                return false;
            }

            if (!pendingHairVariantRequests[modelKey]) {
                const url = hairVariantsUrl.replace('__MODEL__', encodeURIComponent(modelKey));

                pendingHairVariantRequests[modelKey] = fetch(url, {
                    headers: {
                        Accept: 'application/json',
                    },
                })
                    .then((response) => (response.ok ? response.json() : null))
                    .then((model) => {
                        if (!model?.profiles) {
                            return false;
                        }

                        hairVariantManifest[modelKey] = model;
                        registerHairAssets(model);
                        loadedHairModels.add(modelKey);

                        return true;
                    })
                    .catch(() => false)
                    .finally(() => {
                        delete pendingHairVariantRequests[modelKey];
                    });
            }

            return pendingHairVariantRequests[modelKey];
        };
        const getSelectedHairModel = () => getChecked('hair_asset')?.dataset.hairModel || defaultHairModel;
        const getBodyVariantInputs = () => Array.from(root.querySelectorAll('[name="body_variant_asset"]'));
        const getCheckedBodyVariant = () => getBodyVariantInputs().find((input) => input.checked) || null;
        const getBodyVariantForBody = (bodyKey) => currentBodyVariant[bodyKey] || 'original';
        const setBodyVariantForBody = (bodyKey, variantKey) => {
            currentBodyVariant[bodyKey] = variantKey;
        };
        const getBodyVariantInputsForParent = (parentKey) => getBodyVariantInputs().filter((input) => input.dataset.bodyVariantParent === parentKey);

        const getState = () => ({
            nickname: nicknameInput?.value.trim() || '',
            bodyAsset: getChecked('body_asset'),
            hairAsset: getChecked('hair_asset'),
            starterClass: getChecked('starter_choice'),
        });

        const renderHairVariantPanel = (modelKey) => {
            const panel = hairVariantPanels.find((item) => item.dataset.hairVariantPanel === modelKey);

            if (!panel) {
                return;
            }

            const state = getState();
            const currentProfile = getCurrentBodyProfile();
            const bodyKey = state.bodyAsset?.value || defaultBodyKey;
            const selectedHairKey = state.hairAsset?.value || defaultHairKey;
            const hairScale = state.bodyAsset?.dataset.avatarHairScale || defaultHairScale;
            const hairOffsetY = state.bodyAsset?.dataset.avatarHairOffsetY || defaultHairOffsetY;
            const variants = hairVariantManifest?.[modelKey]?.profiles?.[currentProfile] || [];
            const previewStyle = `--avatar-hair-scale: ${hairScale}; --avatar-hair-offset-y: ${hairOffsetY};`;

            panel.innerHTML = variants.length === 0
                ? ''
                : `
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                        ${variants.map((variant) => `
                            <label class="block cursor-pointer">
                                <input
                                    type="radio"
                                    name="hair_asset"
                                    value="${escapeHtml(variant.key)}"
                                    class="peer sr-only"
                                    data-hair-model="${escapeHtml(modelKey)}"
                                    data-hair-profile="${escapeHtml(currentProfile)}"
                                    data-avatar-hair-key="${escapeHtml(variant.key)}"
                                    data-avatar-hair-color="${escapeHtml(variant.hair_color || '#1E1E1E')}"
                                    ${selectedHairKey === variant.key ? 'checked' : ''}
                                >
                                <span class="ob-variant-card">
                                    <span class="flex flex-col items-center gap-2">
                                        <span class="ob-avatar-mini-preview ob-avatar-choice-preview ob-avatar-choice-preview--hair-variant" style="${escapeHtml(previewStyle)}">
                                            <span class="ob-avatar-choice-preview__layer">
                                                ${getTemplateHtml('body', bodyKey)}
                                                ${getHairTemplateHtml(variant.key)}
                                            </span>
                                        </span>
                                        <span class="space-y-0.5 text-center">
                                            <span class="block text-sm font-semibold text-slate-900">${escapeHtml(variant.label)}</span>
                                        </span>
                                    </span>
                                </span>
                            </label>
                        `).join('')}
                    </div>
                `;
        };

        const ensureCompatibleHairSelection = () => {
            const currentProfile = getCurrentBodyProfile();
            const selectedHairInput = getChecked('hair_asset');
            const selectedModelKey = selectedHairInput?.dataset.hairModel || getSelectedHairModel();

            renderHairVariantPanel(selectedModelKey);

            if (selectedHairInput && selectedHairInput.dataset.hairProfile === currentProfile) {
                return;
            }

            const currentModelCard = hairModelCards.find((card) => card.dataset.hairModel === selectedModelKey && supportsProfile(card, currentProfile));
            const fallbackCard = currentModelCard || hairModelCards.find((card) => supportsProfile(card, currentProfile));

            if (!fallbackCard) {
                return;
            }

            renderHairVariantPanel(fallbackCard.dataset.hairModel);

            const fallbackVariantKey = getDefaultVariantForProfile(fallbackCard, currentProfile);
            const fallbackInput = getHairInputByValue(fallbackVariantKey)
                || getHairInputsForModelAndProfile(fallbackCard.dataset.hairModel, currentProfile)[0]
                || getHairInputsForModel(fallbackCard.dataset.hairModel)[0]
                || null;

            if (fallbackInput) {
                fallbackInput.checked = true;
            }
        };

        const syncHairModelUi = () => {
            const state = getState();
            const selectedModel = state.hairAsset?.dataset.hairModel || defaultHairModel;
            const currentProfile = getCurrentBodyProfile();
            const currentBodyKey = state.bodyAsset?.value || defaultBodyKey;

            hairModelCards.forEach((card) => {
                const visible = supportsProfile(card, currentProfile);
                card.classList.toggle('hidden', !visible);
                card.dataset.selected = visible && card.dataset.hairModel === selectedModel ? 'true' : 'false';

                const previewTarget = card.querySelector('[data-hair-model-preview]');
                const previewVariantKey = getDefaultVariantForProfile(card, currentProfile);
                const previewAsset = hairAssetManifest?.[previewVariantKey] || getDefaultAssetForProfile(card, currentProfile);

                if (previewTarget && previewVariantKey) {
                    previewTarget.innerHTML = [
                        getTemplateHtml('body', currentBodyKey),
                        renderSheetLayerHtml(previewAsset, 'ob-avatar-asset ob-avatar-svg ob-avatar-svg--hair'),
                    ].join('');
                }
            });

            hairVariantPanels.forEach((panel) => {
                const isActivePanel = panel.dataset.hairVariantPanel === selectedModel;
                panel.classList.toggle('hidden', !isActivePanel);
            });

            renderHairVariantPanel(selectedModel);

            const activeCard = hairModelCards.find((card) => card.dataset.hairModel === selectedModel);

            if (activeHairStyleLabel) {
                activeHairStyleLabel.textContent = activeCard?.dataset.hairStyleLabel || '';
            }
        };

        const syncBodyVariantUi = () => {
            const state = getState();
            const bodyKey = state.bodyAsset?.value || defaultBodyKey;
            const variants = bodyVariantManifest?.[bodyKey] || [];
            const hasVariants = variants.length > 0;

            if (bodyVariantSection) {
                bodyVariantSection.classList.toggle('hidden', !hasVariants);
            }

            bodyVariantPanels.forEach((panel) => {
                const isActivePanel = panel.dataset.bodyVariantPanel === bodyKey;
                panel.classList.toggle('hidden', !isActivePanel);
            });

            if (hasVariants) {
                const inputsForBody = getBodyVariantInputsForParent(bodyKey);
                const savedVariant = getBodyVariantForBody(bodyKey);

                inputsForBody.forEach((input) => {
                    input.checked = input.value === savedVariant;
                });

                const variantLabel = savedVariant === 'original' ? 'Original' : savedVariant;
                if (activeBodyVariantLabel) {
                    activeBodyVariantLabel.textContent = variantLabel;
                }
            }
        };

        const getBodyAssetForPreview = () => {
            const state = getState();
            const bodyKey = state.bodyAsset?.value || defaultBodyKey;
            const variantKey = getBodyVariantForBody(bodyKey);

            if (variantKey === 'original') {
                return { type: 'body', key: bodyKey, outfitBodyKey: bodyKey };
            }

            return { type: 'body-variant', key: variantKey, outfitBodyKey: bodyKey };
        };

        const updateAvatarPreview = () => {
            ensureCompatibleHairSelection();

            const state = getState();
            const bodyAsset = getBodyAssetForPreview();
            const hairKey = state.hairAsset?.value || defaultHairKey;
            const outfitPresetKey = state.starterClass?.dataset.avatarOutfitPresetKey || defaultOutfitPresetKey;
            const accent = state.starterClass?.dataset.avatarAccent || defaultOutfitAccent;
            const skin = state.bodyAsset?.dataset.avatarSkin || defaultSkin;
            const hair = state.hairAsset?.dataset.avatarHairColor || '#1E1E1E';
            const hairScale = state.bodyAsset?.dataset.avatarHairScale || defaultHairScale;
            const hairOffsetY = state.bodyAsset?.dataset.avatarHairOffsetY || defaultHairOffsetY;
            const outfitKey = outfitPresetKey ? getOutfitTemplateKey(outfitPresetKey, bodyAsset.outfitBodyKey) : '';

            if (avatarLayer) {
                avatarLayer.innerHTML = [
                    getTemplateHtml(bodyAsset.type, bodyAsset.key),
                    outfitKey ? getTemplateHtml('outfit-preset-base', outfitKey) : '',
                    getHairTemplateHtml(hairKey, 'eager'),
                    outfitKey ? getTemplateHtml('outfit-preset-overlay', outfitKey) : '',
                ].join('');

                const palette = {
                    ...buildSkinPalette(skin),
                    ...buildHairPalette(hair),
                    ...buildOutfitPalette(accent),
                };

                Object.entries(palette).forEach(([variable, value]) => {
                    avatarLayer.style.setProperty(variable, value);
                    avatarStage?.style.setProperty(variable, value);
                });

                avatarLayer.style.setProperty('--avatar-hair-scale', hairScale);
                avatarLayer.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
                avatarStage?.style.setProperty('--avatar-hair-scale', hairScale);
                avatarStage?.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
            }

            classAvatarPreviews.forEach((preview) => {
                const previewOutfitPresetKey = preview.dataset.classOutfitPresetKey || '';
                const previewAccent = preview.dataset.classAccent || defaultOutfitAccent;
                const previewOutfitKey = previewOutfitPresetKey ? getOutfitTemplateKey(previewOutfitPresetKey, bodyAsset.outfitBodyKey) : '';

                preview.innerHTML = [
                    getTemplateHtml(bodyAsset.type, bodyAsset.key),
                    previewOutfitKey ? getTemplateHtml('outfit-preset-base', previewOutfitKey) : '',
                    getHairTemplateHtml(hairKey),
                    previewOutfitKey ? getTemplateHtml('outfit-preset-overlay', previewOutfitKey) : '',
                ].join('');

                const previewPalette = {
                    ...buildSkinPalette(skin),
                    ...buildHairPalette(hair),
                    ...buildOutfitPalette(previewAccent),
                };

                Object.entries(previewPalette).forEach(([variable, value]) => {
                    preview.style.setProperty(variable, value);
                });

                preview.style.setProperty('--avatar-hair-scale', hairScale);
                preview.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
            });

            const nickname = state.nickname || avatarNickname?.dataset.defaultNickname || '';

            if (avatarNickname) {
                avatarNickname.textContent = nickname;
            }

            if (summaryNickname) {
                summaryNickname.textContent = nickname;
            }

            const classTitle = state.starterClass?.dataset.classTitle || avatarClass?.dataset.placeholder || '';
            const classIcon = state.starterClass?.dataset.classIcon || '';
            const classLabel = classTitle ? `${classIcon} ${classTitle}`.trim() : avatarClass?.dataset.placeholder || '';

            if (avatarClass) {
                avatarClass.textContent = classLabel;
            }

            if (summaryClass) {
                summaryClass.textContent = classLabel;
            }

            summaryCompanions.forEach((item) => {
                item.dataset.active = state.starterClass && item.dataset.summaryCompanion === state.starterClass.value ? 'true' : 'false';
            });

            syncHairModelUi();
            animateAvatarStage(avatarStage);
        };

        const initSlider = (sliderRoot) => {
            const viewport = sliderRoot.querySelector('[data-slider-viewport]');
            const track = sliderRoot.querySelector('[data-slider-track]');
            const previousButtons = Array.from(sliderRoot.querySelectorAll('[data-slider-prev]'));
            const nextButtons = Array.from(sliderRoot.querySelectorAll('[data-slider-next]'));

            if (!viewport || !track) {
                return;
            }

            const slideStep = () => {
                const firstSlide = track.firstElementChild;

                if (!(firstSlide instanceof HTMLElement)) {
                    return viewport.clientWidth * 0.8;
                }

                const gap = Number.parseFloat(window.getComputedStyle(track).columnGap || window.getComputedStyle(track).gap || '0');

                return firstSlide.getBoundingClientRect().width + gap;
            };

            const syncSlider = () => {
                const maxScrollLeft = viewport.scrollWidth - viewport.clientWidth;
                const atStart = viewport.scrollLeft <= 4;
                const atEnd = viewport.scrollLeft >= maxScrollLeft - 4;

                previousButtons.forEach((button) => {
                    button.disabled = atStart;
                });

                nextButtons.forEach((button) => {
                    button.disabled = atEnd;
                });
            };

            previousButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    viewport.scrollBy({
                        left: -slideStep(),
                        behavior: 'smooth',
                    });
                });
            });

            nextButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    viewport.scrollBy({
                        left: slideStep(),
                        behavior: 'smooth',
                    });
                });
            });

            viewport.addEventListener('scroll', syncSlider, { passive: true });
            window.addEventListener('resize', syncSlider);
            syncSlider();
        };

        const stepOneIsValid = () => {
            const state = getState();

            return state.nickname.length >= 3
                && Boolean(state.bodyAsset)
                && Boolean(state.hairAsset);
        };

        const stepTwoIsValid = () => Boolean(getState().starterClass);

        const syncButtons = () => {
            buttonsNeedingStepOne.forEach((button) => {
                button.disabled = !stepOneIsValid();
            });

            buttonsNeedingStepTwo.forEach((button) => {
                button.disabled = !stepTwoIsValid();
            });

            if (submitButton) {
                submitButton.disabled = !(stepOneIsValid() && stepTwoIsValid());
            }
        };

        const setCurrentStep = (step) => {
            currentStep = Math.min(totalSteps, Math.max(1, step));

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', Number(panel.dataset.onboardingPanel) !== currentStep);
            });

            stepIndicators.forEach((indicator) => {
                const indicatorStep = Number(indicator.dataset.stepIndicator);
                indicator.dataset.state = indicatorStep === currentStep ? 'current' : indicatorStep < currentStep ? 'done' : 'upcoming';
            });

            if (progressLabel) {
                progressLabel.textContent = progressLabel.dataset.progressTemplate
                    .replace(':current', String(currentStep))
                    .replace(':total', String(totalSteps));
            }

            if (stepName) {
                const activeIndicator = stepIndicators.find((indicator) => Number(indicator.dataset.stepIndicator) === currentStep);
                stepName.textContent = activeIndicator?.dataset.stepLabel || '';
            }
        };

        root.addEventListener('change', (event) => {
            if (!(event.target instanceof HTMLInputElement)) {
                return;
            }

            if (event.target.name.startsWith('body_variant_asset_') && event.target.checked) {
                const bodyKey = event.target.dataset.bodyVariantParent;
                const variantValue = event.target.value;
                setBodyVariantForBody(bodyKey, variantValue);
                const bodyAsset = getBodyAssetForPreview();
                const hairKey = getChecked('hair_asset')?.value || defaultHairKey;
                const outfitPresetKey = getChecked('starter_choice')?.dataset.avatarOutfitPresetKey || defaultOutfitPresetKey;
                const accent = getChecked('starter_choice')?.dataset.avatarAccent || defaultOutfitAccent;
                const skin = getChecked('body_asset')?.dataset.avatarSkin || defaultSkin;
                const hair = getChecked('hair_asset')?.dataset.avatarHairColor || '#1E1E1E';
                const hairScale = getChecked('body_asset')?.dataset.avatarHairScale || defaultHairScale;
                const hairOffsetY = getChecked('body_asset')?.dataset.avatarHairOffsetY || defaultHairOffsetY;
            const outfitKey = outfitPresetKey ? getOutfitTemplateKey(outfitPresetKey, bodyAsset.outfitBodyKey) : '';

                if (avatarLayer) {
                    avatarLayer.innerHTML = [
                        getTemplateHtml(bodyAsset.type, bodyAsset.key),
                        outfitKey ? getTemplateHtml('outfit-preset-base', outfitKey) : '',
                        getHairTemplateHtml(hairKey, 'eager'),
                        outfitKey ? getTemplateHtml('outfit-preset-overlay', outfitKey) : '',
                    ].join('');

                    const palette = {
                        ...buildSkinPalette(skin),
                        ...buildHairPalette(hair),
                        ...buildOutfitPalette(accent),
                    };

                    Object.entries(palette).forEach(([variable, value]) => {
                        avatarLayer.style.setProperty(variable, value);
                        avatarStage?.style.setProperty(variable, value);
                    });

                    avatarLayer.style.setProperty('--avatar-hair-scale', hairScale);
                    avatarLayer.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
                    avatarStage?.style.setProperty('--avatar-hair-scale', hairScale);
                    avatarStage?.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
                }

                classAvatarPreviews.forEach((preview) => {
                    const previewOutfitPresetKey = preview.dataset.classOutfitPresetKey || '';
                    const previewAccent = preview.dataset.classAccent || defaultOutfitAccent;
                    const previewOutfitKey = previewOutfitPresetKey ? getOutfitTemplateKey(previewOutfitPresetKey, bodyAsset.outfitBodyKey) : '';

                    preview.innerHTML = [
                        getTemplateHtml(bodyAsset.type, bodyAsset.key),
                        previewOutfitKey ? getTemplateHtml('outfit-preset-base', previewOutfitKey) : '',
                        getHairTemplateHtml(hairKey),
                        previewOutfitKey ? getTemplateHtml('outfit-preset-overlay', previewOutfitKey) : '',
                    ].join('');

                    const previewPalette = {
                        ...buildSkinPalette(skin),
                        ...buildHairPalette(hair),
                        ...buildOutfitPalette(previewAccent),
                    };

                    Object.entries(previewPalette).forEach(([variable, value]) => {
                        preview.style.setProperty(variable, value);
                    });

                    preview.style.setProperty('--avatar-hair-scale', hairScale);
                    preview.style.setProperty('--avatar-hair-offset-y', hairOffsetY);
                });

                animateAvatarStage(avatarStage);
                syncButtons();
                return;
            }

            updateAvatarPreview();
            syncBodyVariantUi();
            syncButtons();
        });

        hairModelCards.forEach((card) => {
            card.addEventListener('click', async () => {
                const currentProfile = getCurrentBodyProfile();
                const modelKey = card.dataset.hairModel;
                const fallbackVariantKey = getDefaultVariantForProfile(card, currentProfile);
                const panel = hairVariantPanels.find((item) => item.dataset.hairVariantPanel === modelKey);

                if (panel && !loadedHairModels.has(modelKey)) {
                    panel.innerHTML = '<div class="rounded-[1.2rem] border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-600">Chargement...</div>';
                }

                await loadHairVariants(modelKey);
                renderHairVariantPanel(modelKey);

                const fallbackInput = getHairInputByValue(fallbackVariantKey)
                    || getHairInputsForModelAndProfile(modelKey, currentProfile)[0]
                    || getHairInputsForModel(modelKey)[0]
                    || null;

                if (!fallbackInput) {
                    return;
                }

                fallbackInput.checked = true;
                updateAvatarPreview();
                syncBodyVariantUi();
                syncButtons();
            });
        });

        root.querySelectorAll('[data-step-next]').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.requires === 'step-1' && !stepOneIsValid()) {
                    return;
                }

                if (button.dataset.requires === 'step-2' && !stepTwoIsValid()) {
                    return;
                }

                setCurrentStep(Number(button.dataset.stepNext));
            });
        });

        root.querySelectorAll('[data-step-prev]').forEach((button) => {
            button.addEventListener('click', () => {
                setCurrentStep(Number(button.dataset.stepPrev));
            });
        });

        if (avatarNickname) {
            avatarNickname.dataset.defaultNickname = avatarNickname.textContent.trim();
        }

        if (avatarClass) {
            avatarClass.dataset.placeholder = avatarClass.textContent.trim();
        }

        sliders.forEach(initSlider);
        renderHairVariantPanel(defaultHairModel);
        syncBodyVariantUi();
        updateAvatarPreview();
        syncButtons();

        const initialStep = stepOneIsValid()
            ? (stepTwoIsValid() ? 3 : 2)
            : 1;

        setCurrentStep(initialStep);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOnboarding);
} else {
    initOnboarding();
}
