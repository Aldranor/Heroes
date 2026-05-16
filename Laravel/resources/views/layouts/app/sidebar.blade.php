<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="rs-shell min-h-screen">
        @php
            $layoutUser = auth()->user()->loadMissing('avatar');
            $layoutAvatar = $layoutUser->avatar;
            $layoutAvatarAssets = app(\App\Support\AvatarAssetCatalog::class);
            $layoutAvatarPresetKey = data_get($layoutAvatar?->equipped_items, 'outfit_preset', data_get($layoutAvatar?->equipped_items, 'outfit', $layoutAvatar?->style));
            $layoutAvatarBodyKey = data_get($layoutAvatar?->equipped_items, 'body', config('avatar.defaults.body'));
            $layoutAvatarHairKey = data_get($layoutAvatar?->equipped_items, 'hair', config('avatar.defaults.hair'));
            $layoutAvatarBodyConfig = $layoutAvatarAssets->bodyAsset($layoutAvatarBodyKey) ?? [];
            $layoutAvatarHairConfig = $layoutAvatarAssets->hairAsset($layoutAvatarHairKey) ?? [];
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="border-e border-amber-200/10 bg-[#171412]/95 text-stone-100 backdrop-blur">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="rs-sidebar-card mx-3 mb-4 rounded-[1.6rem] p-3">
                <div class="relative overflow-hidden rounded-[1.2rem] border border-white/6 bg-white/5">
                    <div class="absolute inset-x-0 top-0 h-16 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.14),_transparent_70%)]"></div>
                    <x-onboarding.avatar-mini-preview
                        class="rs-hub-avatar-preview relative z-10"
                        :body-key="$layoutAvatarBodyKey"
                        :hair-key="$layoutAvatarHairKey"
                        :outfit-preset-key="$layoutAvatarPresetKey"
                        :equipped-items="$layoutAvatar?->equipped_items ?? []"
                        :skin="data_get($layoutAvatar?->colors, 'skin', data_get($layoutAvatarBodyConfig, 'skin', config('avatar.defaults.skin')))"
                        :hair-color="data_get($layoutAvatar?->colors, 'hair', data_get($layoutAvatarHairConfig, 'color', config('avatar.defaults.hair_color')))"
                        :accent="data_get($layoutAvatar?->colors, 'accent', '#D97745')"
                        :hair-scale="data_get($layoutAvatarBodyConfig, 'hair_scale', '1.00')"
                        :hair-offset-y="data_get($layoutAvatarBodyConfig, 'hair_offset_y', '0%')"
                    />
                </div>
                <div class="mt-3 px-1">
                    <p class="rs-label">Héros</p>
                    <h2 class="rs-display mt-1 text-xl text-stone-50">{{ $layoutAvatar?->nickname ?? $layoutUser->name }}</h2>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rs-stat-chip">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-stone-400">{{ __('navigation.level') }}</p>
                        <p class="mt-1 text-lg font-semibold text-stone-100">Lv. {{ $layoutUser->level }}</p>
                    </div>
                    <div class="rs-stat-chip">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-stone-400">{{ __('navigation.coins') }}</p>
                        <p class="mt-1 text-lg font-semibold text-stone-100">{{ $layoutUser->coins }}</p>
                    </div>
                </div>
            </div>

            <flux:sidebar.nav class="px-3">
                <flux:sidebar.group :heading="__('navigation.districts')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('navigation.hub') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('training.create')" :current="request()->routeIs('training.*')" wire:navigate>
                        {{ __('navigation.training') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="route('skills.index')" :current="request()->routeIs('skills.*')" wire:navigate>
                        {{ __('navigation.skills') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="mx-3 mb-3 rounded-[1.6rem] border border-white/8 bg-white/4 px-4 py-4">
                <p class="rs-label">{{ __('navigation.next_step_title') }}</p>
                <p class="mt-2 text-sm leading-6 text-stone-300">
                    {{ __('navigation.next_step_body') }}
                </p>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-b border-white/8 bg-[#171412]/90 backdrop-blur lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('navigation.settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('navigation.log_out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
