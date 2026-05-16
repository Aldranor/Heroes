<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('onboarding.page_title')])
    </head>
    <body class="ob-shell min-h-screen text-stone-950 antialiased">
        @inject('avatarAssets', 'App\Support\AvatarAssetCatalog')
        @inject('avatarRenderer', 'App\Support\AvatarSvgRenderer')
        @php
            $defaultBodyAsset = old('body_asset', config('avatar.defaults.body'));
            $defaultHairAsset = old('hair_asset', config('avatar.defaults.hair'));
            $defaultStarterClass = old('starter_choice');
            $defaultOutfitPresetKey = $defaultStarterClass
                ? (config("avatar.class_outfits.{$defaultStarterClass}.preset") ?? config("avatar.class_outfits.{$defaultStarterClass}.outfit"))
                : '';
            $defaultAccent = $defaultStarterClass ? config("avatar.class_outfits.{$defaultStarterClass}.accent", '#5B6CFF') : '#5B6CFF';
            $bodyAssets = $avatarAssets->bodyAssets();
            $bodyVariants = $avatarAssets->bodyVariants();
            $bodyVariantsByParent = collect($bodyVariants)->groupBy('parent_key')->all();
            $defaultBodyConfig = $bodyAssets[$defaultBodyAsset] ?? [];
            $defaultBodyProfile = $defaultBodyConfig['frame_profile'] ?? 'female';
            $defaultSkin = $defaultBodyConfig['skin'] ?? config('avatar.defaults.skin');
            $hairAssets = $avatarAssets->hairAssets();
            $hairModels = $avatarAssets->hairModels();
            $defaultHairConfig = $hairAssets[$defaultHairAsset] ?? [];
            $defaultHairColor = $defaultHairConfig['color'] ?? config('avatar.defaults.hair_color');
            $defaultHairModel = $defaultHairConfig['model_key'] ?? array_key_first($hairModels);
            $defaultHairModelMeta = $defaultHairModel ? ($hairModels[$defaultHairModel] ?? null) : null;
            $defaultHairProfile = $defaultHairConfig['frame_profile'] ?? $defaultBodyProfile;
            $defaultHairPayload = $avatarRenderer->sheetAssetPayload($defaultHairConfig);
            $defaultHairImageStyle = $defaultHairPayload['image_style'] ?? '';
            $initialHairModels = $defaultHairModel && isset($hairModels[$defaultHairModel])
                ? [$defaultHairModel => $hairModels[$defaultHairModel]]
                : [];
            $hairVariantManifest = collect($initialHairModels)->mapWithKeys(function (array $model, string $modelKey) use ($avatarRenderer, $defaultHairColor) {
                return [
                    $modelKey => [
                        'profiles' => collect($model['profiles'] ?? [])->mapWithKeys(function (array $group, string $profile) use ($avatarRenderer, $defaultHairColor) {
                            return [
                                $profile => collect($group['variants'] ?? [])->map(function (array $variant) use ($avatarRenderer, $defaultHairColor) {
                                    return [
                                        'key' => $variant['key'],
                                        'label' => $variant['variant_label'] ?? $variant['label'],
                                        'hair_color' => $variant['color'] ?? $defaultHairColor,
                                        'src' => $avatarRenderer->sheetAssetSrc($variant),
                                    ];
                                })->values()->all(),
                            ];
                        })->all(),
                    ],
                ];
            })->all();
            $bodyVariantManifest = collect($bodyVariants)->groupBy('parent_key')->map(function ($variants) {
                return $variants->map(function ($variant) {
                    return [
                        'key' => $variant['key'],
                        'label' => $variant['variant_label'] ?? $variant['label'],
                        'parent_key' => $variant['parent_key'],
                    ];
                })->values()->all();
            })->all();
            $classOutfits = config('avatar.class_outfits', []);
            $classOptions = [
                'stratege' => trans('onboarding.class.options.stratege'),
                'observateur' => trans('onboarding.class.options.observateur'),
                'eclaireur' => trans('onboarding.class.options.eclaireur'),
            ];
        @endphp

        <main class="mx-auto min-h-screen w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('onboarding.hero.label') }}</p>
                    <h2 class="rs-display mt-1 text-2xl text-stone-900">{{ __('onboarding.hero.title') }}</h2>
                    <p class="mt-2 text-sm text-stone-600">{{ __('onboarding.brand_hint') }}</p>
                </div>
                <span class="rounded-full border border-stone-300 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-700">
                    {{ __('onboarding.steps_total') }}
                </span>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-[1.4rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('onboarding.avatar.store') }}"
                class="grid gap-6 lg:grid-cols-[25rem_minmax(0,1fr)] lg:items-start"
                data-onboarding-root
                data-total-steps="3"
                data-default-body-key="{{ $defaultBodyAsset }}"
                data-default-hair-key="{{ $defaultHairAsset }}"
                data-default-outfit-preset-key="{{ $defaultOutfitPresetKey }}"
                data-default-outfit-accent="{{ $defaultAccent }}"
                data-default-skin="{{ $defaultSkin }}"
                data-default-body-profile="{{ $defaultBodyProfile }}"
                data-default-hair-model="{{ $defaultHairModel }}"
                data-default-hair-scale="{{ $defaultBodyConfig['hair_scale'] ?? '1.00' }}"
                data-default-hair-offset-y="{{ $defaultBodyConfig['hair_offset_y'] ?? '0%' }}"
                data-default-hair-image-style="{{ $defaultHairImageStyle }}"
                data-hair-variants-url="{{ route('onboarding.avatar.hair-variants', ['model' => '__MODEL__']) }}"
            >
                @csrf

                <script type="application/json" data-body-variant-manifest>@json($bodyVariantManifest)</script>

                <aside class="space-y-4 lg:sticky lg:top-6">
                    <x-onboarding.avatar-preview
                        :default-body-key="$defaultBodyAsset"
                        :default-hair-key="$defaultHairAsset"
                        :default-outfit-preset-key="$defaultOutfitPresetKey"
                        :default-skin="$defaultSkin"
                        :default-hair-color="$defaultHairColor"
                        :default-accent="$defaultAccent"
                        :default-hair-scale="$defaultBodyConfig['hair_scale'] ?? '1.00'"
                        :default-hair-offset-y="$defaultBodyConfig['hair_offset_y'] ?? '0%'"
                        :default-nickname="__('onboarding.default_nickname')"
                        :default-class="__('onboarding.class_placeholder')"
                    />

                    <div class="rounded-[1.6rem] border border-white/10 bg-white/85 px-4 py-4 shadow-[0_18px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-sm font-semibold text-stone-800"
                                data-progress-label
                                data-progress-template="{{ __('onboarding.progress', ['current' => ':current', 'total' => ':total']) }}"
                            >
                                {{ __('onboarding.progress', ['current' => 1, 'total' => 3]) }}
                            </p>
                            <span class="text-xs font-medium uppercase tracking-[0.18em] text-stone-500" data-step-name>
                                {{ __('onboarding.stepper.avatar') }}
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            @foreach ([
                                1 => __('onboarding.stepper.avatar'),
                                2 => __('onboarding.stepper.class'),
                                3 => __('onboarding.stepper.summary'),
                            ] as $index => $label)
                                <div
                                    class="ob-step-indicator"
                                    data-step-indicator="{{ $index }}"
                                    data-step-label="{{ $label }}"
                                >
                                    <span class="ob-step-dot">{{ $index }}</span>
                                    <span class="mt-2 block text-[11px] font-semibold uppercase tracking-[0.16em]">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>

                <div class="space-y-4">
                    <x-onboarding.step
                        step="1"
                        :active="true"
                        :eyebrow="__('onboarding.avatar.eyebrow')"
                        :title="__('onboarding.avatar.title')"
                        :description="__('onboarding.avatar.description')"
                    >
                        <section class="rounded-[1.7rem] border border-stone-200 bg-stone-50/80 p-4 sm:p-5">
                            <div class="space-y-2">
                                <label for="nickname" class="text-sm font-semibold text-stone-800">{{ __('onboarding.avatar.nickname_label') }}</label>
                                <input
                                    id="nickname"
                                    name="nickname"
                                    type="text"
                                    value="{{ old('nickname') }}"
                                    maxlength="24"
                                    required
                                    autocomplete="nickname"
                                    class="w-full rounded-[1.25rem] border border-stone-200 bg-white px-4 py-4 text-base text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-stone-400 focus:ring-2 focus:ring-stone-300/70"
                                    placeholder="{{ __('onboarding.avatar.nickname_placeholder') }}"
                                    data-avatar-field="nickname"
                                >
                                <p class="text-sm text-stone-500">{{ __('onboarding.avatar.nickname_help') }}</p>
                            </div>
                        </section>

                        <fieldset class="space-y-3" data-slider>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <legend class="text-sm font-semibold tracking-[0.01em] text-stone-800">{{ __('onboarding.avatar.body_label') }}</legend>
                                    <p class="mt-1 text-sm text-stone-500">{{ __('onboarding.avatar.body_help') }}</p>
                                </div>
                                <div class="hidden items-center gap-2 sm:flex">
                                    <button type="button" class="ob-slider__button" data-slider-prev aria-label="{{ __('onboarding.slider_previous') }}">‹</button>
                                    <button type="button" class="ob-slider__button" data-slider-next aria-label="{{ __('onboarding.slider_next') }}">›</button>
                                </div>
                            </div>

                            <div class="ob-slider">
                                <div class="ob-slider__viewport" data-slider-viewport>
                                    <div class="ob-slider__track" data-slider-track>
                                        @foreach ($bodyAssets as $key => $body)
                                            <div class="ob-slider__slide">
                                                <x-onboarding.style-card
                                                    name="body_asset"
                                                    :value="$key"
                                                    :title="$body['label']"
                                                    :description="$body['description']"
                                                    :checked="$defaultBodyAsset === $key"
                                                    card-class="ob-style-card--body"
                                                    data-avatar-body-key="{{ $key }}"
                                                    data-avatar-profile="{{ $body['frame_profile'] ?? 'female' }}"
                                                    data-avatar-skin="{{ $body['skin'] ?? $defaultSkin }}"
                                                    data-avatar-hair-scale="{{ $body['hair_scale'] ?? '1.00' }}"
                                                    data-avatar-hair-offset-y="{{ $body['hair_offset_y'] ?? '0%' }}"
                                                >
                                                    <x-onboarding.avatar-mini-preview
                                                        class="ob-avatar-choice-preview ob-avatar-choice-preview--body"
                                                        :body-key="$key"
                                                    />
                                                </x-onboarding.style-card>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <section
                            class="hidden rounded-[1.7rem] border border-stone-200 bg-stone-50/80 p-4 sm:p-5"
                            data-body-variant-section
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-stone-800">{{ __('onboarding.avatar.body_variant_label') }}</p>
                                    <p class="mt-1 text-sm text-stone-500">{{ __('onboarding.avatar.body_variant_help') }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-stone-600 shadow-sm" data-active-body-variant>
                                </span>
                            </div>

                            <div class="mt-4 space-y-4">
                                @foreach ($bodyAssets as $bodyKey => $body)
                                    @php
                                        $variants = $bodyVariantsByParent[$bodyKey] ?? [];
                                    @endphp
                                    <div
                                        data-body-variant-panel="{{ $bodyKey }}"
                                        class="hidden"
                                    >
                                        @if (count($variants) > 0)
                                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                                                <x-onboarding.asset-card
                                                    name="body_variant_asset_{{ $bodyKey }}"
                                                    value="original"
                                                    :label="__('onboarding.avatar.body_variant_original')"
                                                    :checked="true"
                                                    data-body-variant-key="original"
                                                    data-body-variant-parent="{{ $bodyKey }}"
                                                >
                                                    <x-onboarding.avatar-mini-preview
                                                        class="ob-avatar-choice-preview ob-avatar-choice-preview--body-variant"
                                                        :body-key="$bodyKey"
                                                    />
                                                </x-onboarding.asset-card>

                                                @foreach ($variants as $variant)
                                                    <x-onboarding.asset-card
                                                        name="body_variant_asset_{{ $bodyKey }}"
                                                        :value="$variant['key']"
                                                        :label="$variant['variant_label'] ?? $variant['label']"
                                                        :checked="false"
                                                        data-body-variant-key="{{ $variant['variant_key'] }}"
                                                        data-body-variant-parent="{{ $bodyKey }}"
                                                    >
                                                        <x-onboarding.avatar-mini-preview
                                                            class="ob-avatar-choice-preview ob-avatar-choice-preview--body-variant"
                                                            :body-key="$bodyKey"
                                                            :body-variant-file="$variant['file']"
                                                        />
                                                    </x-onboarding.asset-card>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <fieldset class="space-y-3" data-slider>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <legend class="text-sm font-semibold tracking-[0.01em] text-stone-800">{{ __('onboarding.avatar.hair_style_label') }}</legend>
                                    <p class="mt-1 text-sm text-stone-500">{{ __('onboarding.avatar.hair_style_help') }}</p>
                                </div>
                                <div class="hidden items-center gap-2 sm:flex">
                                    <button type="button" class="ob-slider__button" data-slider-prev aria-label="{{ __('onboarding.slider_previous') }}">‹</button>
                                    <button type="button" class="ob-slider__button" data-slider-next aria-label="{{ __('onboarding.slider_next') }}">›</button>
                                </div>
                            </div>

                            <div class="ob-slider">
                                <div class="ob-slider__viewport" data-slider-viewport>
                                    <div class="ob-slider__track" data-slider-track>
                                        @foreach ($hairModels as $modelKey => $model)
                                            @php
                                                $defaultVariantForFemale = data_get($model, 'profiles.female.default_variant_key', '');
                                                $defaultVariantForMale = data_get($model, 'profiles.male.default_variant_key', '');
                                                $defaultVariantForCurrentProfile = data_get($model, "profiles.{$defaultBodyProfile}.default_variant_key")
                                                    ?? data_get($model, "profiles.{$defaultHairProfile}.default_variant_key")
                                                    ?? $model['default_variant_key']
                                                    ?? '';
                                                $defaultFemaleVariant = collect(data_get($model, 'profiles.female.variants', []))->firstWhere('key', $defaultVariantForFemale);
                                                $defaultMaleVariant = collect(data_get($model, 'profiles.male.variants', []))->firstWhere('key', $defaultVariantForMale);
                                            @endphp
                                            <div class="ob-slider__slide">
                                                <x-onboarding.style-card
                                                    tag="button"
                                                    :title="$model['label']"
                                                    :description="$model['description'] ?? null"
                                                    :selected="$defaultHairModel === $modelKey"
                                                    card-class="ob-style-card--hair"
                                                    data-hair-model-card
                                                    data-hair-model="{{ $modelKey }}"
                                                    data-hair-style-label="{{ $model['label'] }}"
                                                    data-hair-supported-profiles="{{ implode(',', $model['supported_profiles'] ?? []) }}"
                                                    data-hair-default-variant="{{ $defaultVariantForCurrentProfile }}"
                                                    data-hair-default-variant-female="{{ $defaultVariantForFemale }}"
                                                    data-hair-default-variant-male="{{ $defaultVariantForMale }}"
                                                    data-hair-default-src-female="{{ $avatarRenderer->sheetAssetSrc($defaultFemaleVariant) ?? '' }}"
                                                    data-hair-default-src-male="{{ $avatarRenderer->sheetAssetSrc($defaultMaleVariant) ?? '' }}"
                                                >
                                                    <x-onboarding.avatar-mini-preview
                                                        class="ob-avatar-choice-preview ob-avatar-choice-preview--hair"
                                                        :body-key="$defaultBodyAsset"
                                                        :hair-key="$defaultVariantForCurrentProfile"
                                                        data-hair-model-preview
                                                    />
                                                </x-onboarding.style-card>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <section class="rounded-[1.7rem] border border-stone-200 bg-stone-50/80 p-4 sm:p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-stone-800">{{ __('onboarding.avatar.hair_variant_label') }}</p>
                                    <p class="mt-1 text-sm text-stone-500">{{ __('onboarding.avatar.hair_variant_help') }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-stone-600 shadow-sm" data-active-hair-style>
                                    {{ $defaultHairModelMeta['label'] ?? '' }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-4">
                                <script type="application/json" data-hair-variant-manifest>@json($hairVariantManifest)</script>
                                @foreach ($hairModels as $modelKey => $model)
                                    <div
                                        data-hair-variant-panel="{{ $modelKey }}"
                                        @class(['hidden' => $defaultHairModel !== $modelKey])
                                    ></div>
                                @endforeach
                            </div>
                        </section>

                        <div class="flex justify-end pt-2">
                            <x-onboarding.primary-button data-step-next="2" data-requires="step-1">
                                {{ __('onboarding.avatar.next') }}
                            </x-onboarding.primary-button>
                        </div>
                    </x-onboarding.step>

                    <x-onboarding.step
                        step="2"
                        :eyebrow="__('onboarding.class.eyebrow')"
                        :title="__('onboarding.class.title')"
                        :description="__('onboarding.class.description')"
                    >
                        <div class="rounded-[1.7rem] border border-stone-200 bg-stone-50/80 p-4 sm:p-5">
                            <p class="text-sm text-stone-600">{{ __('onboarding.class.hint') }}</p>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-3">
                            @foreach ($classOptions as $value => $option)
                                <x-onboarding.class-card
                                    name="starter_choice"
                                    :value="$value"
                                    :title="$option['title']"
                                    :description="$option['description']"
                                    :identity="$option['identity'] ?? null"
                                    :icon="$option['icon']"
                                    :outfit-preset-key="($classOutfits[$value]['preset'] ?? $classOutfits[$value]['outfit'])"
                                    :outfit-accent="$classOutfits[$value]['accent']"
                                    :body-key="$defaultBodyAsset"
                                    :hair-key="$defaultHairAsset"
                                    :skin="$defaultSkin"
                                    :hair-color="$defaultHairColor"
                                    :hair-scale="$defaultBodyConfig['hair_scale'] ?? '1.00'"
                                    :hair-offset-y="$defaultBodyConfig['hair_offset_y'] ?? '0%'"
                                    :checked="$defaultStarterClass === $value"
                                />
                            @endforeach
                        </div>

                        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                            <x-onboarding.primary-button variant="secondary" data-step-prev="1">
                                {{ __('onboarding.class.back') }}
                            </x-onboarding.primary-button>
                            <x-onboarding.primary-button data-step-next="3" data-requires="step-2">
                                {{ __('onboarding.class.next') }}
                            </x-onboarding.primary-button>
                        </div>
                    </x-onboarding.step>

                    <x-onboarding.step
                        step="3"
                        :eyebrow="__('onboarding.summary.eyebrow')"
                        :title="__('onboarding.summary.title')"
                        :description="__('onboarding.summary.description')"
                    >
                        <div class="grid gap-4">
                            <section class="rounded-[1.5rem] border border-stone-200 bg-stone-50 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('onboarding.avatar.nickname_label') }}</p>
                                <p class="mt-2 rs-display text-3xl text-stone-950" data-summary-nickname>{{ old('nickname', __('onboarding.default_nickname')) }}</p>
                            </section>

                            <section class="rounded-[1.5rem] border border-stone-200 bg-stone-50 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('onboarding.summary.chosen_class') }}</p>
                                <p class="mt-2 text-lg font-semibold text-stone-900" data-summary-class>{{ __('onboarding.class_placeholder') }}</p>
                            </section>

                            <section class="rounded-[1.6rem] border border-stone-200 bg-stone-50 px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('onboarding.summary.team_title') }}</p>
                                        <p class="mt-2 text-sm text-stone-600">{{ __('onboarding.summary.team_hint') }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3">
                                    @foreach ($classOptions as $slug => $option)
                                        <x-onboarding.companion-preview
                                            :slug="$slug"
                                            :icon="$option['icon']"
                                            :name="$option['title']"
                                            :description="$option['description']"
                                        />
                                    @endforeach
                                </div>
                            </section>
                        </div>

                        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                            <x-onboarding.primary-button variant="secondary" data-step-prev="2">
                                {{ __('onboarding.summary.back') }}
                            </x-onboarding.primary-button>
                            <x-onboarding.primary-button type="submit" data-submit-onboarding>
                                {{ __('onboarding.summary.submit') }}
                            </x-onboarding.primary-button>
                        </div>
                    </x-onboarding.step>
                </div>
            </form>
        </main>
    </body>
</html>
