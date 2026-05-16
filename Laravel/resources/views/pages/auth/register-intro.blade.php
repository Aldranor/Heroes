<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => __('register_intro.page_title')])
    </head>
    <body class="rs-shell min-h-screen antialiased">
        @php
            $admissionBackgroundUrl = route('avatar.assets.show', ['path' => 'backgrounds/set3_1_0.png']);
        @endphp

        <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-6 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.18em] text-stone-200" wire:navigate>
                    <span class="flex size-9 items-center justify-center rounded-xl border border-white/10 bg-white/8">
                        <x-app-logo-icon class="size-6 fill-current text-stone-100" />
                    </span>
                    <span>{{ __('hub.hero.title') }}</span>
                </a>

                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-200 transition hover:border-amber-200/30 hover:text-amber-50" wire:navigate>
                        {{ __('register_intro.secondary_cta') }}
                    </a>
                @endif
            </header>

            <section class="grid gap-5 lg:grid-cols-[1.08fr_0.92fr] lg:items-stretch">
                <div class="rs-panel rs-hero rs-panel-enter rounded-[2rem] p-6 sm:p-8 lg:p-10">
                    <img
                        src="{{ $admissionBackgroundUrl }}"
                        alt="{{ __('register_intro.illustration_alt') }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-[0.35] saturate-75"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(90deg,_rgba(16,14,12,0.96)_0%,_rgba(16,14,12,0.86)_44%,_rgba(16,14,12,0.52)_100%)]"></div>
                    <div class="relative flex h-full flex-col justify-between gap-10">
                        <div class="max-w-2xl">
                            <p class="rs-label">{{ __('register_intro.eyebrow') }}</p>
                            <h1 class="rs-display mt-3 text-4xl leading-none text-stone-50 sm:text-6xl">
                                {{ __('register_intro.title') }}
                            </h1>
                            <p class="mt-5 max-w-xl text-sm leading-7 text-stone-300 sm:text-base">
                                {{ __('register_intro.body') }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="{{ route('register') }}" class="inline-flex justify-center rounded-full bg-amber-200 px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-stone-950 transition hover:bg-amber-100" wire:navigate>
                                {{ __('register_intro.primary_cta') }}
                            </a>

                            @if (Route::has('login'))
                                <p class="text-sm text-stone-400">
                                    {{ __('register_intro.login_prompt') }}
                                    <a href="{{ route('login') }}" class="font-semibold text-stone-100 underline decoration-amber-200/40 underline-offset-4" wire:navigate>
                                        {{ __('register_intro.secondary_cta') }}
                                    </a>
                                </p>
                            @endif
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (trans('register_intro.microcopy') as $item)
                                <span class="rounded-full border border-white/10 bg-stone-950/45 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-stone-100 backdrop-blur">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <aside class="rs-panel rs-panel-enter rounded-[2rem] p-6 sm:p-7" style="animation-delay: 90ms;">
                    <p class="rs-label">{{ __('register_intro.program_title') }}</p>
                    <h2 class="rs-display mt-3 text-3xl text-stone-50">{{ __('register_intro.program_subtitle') }}</h2>

                    <div class="mt-6 divide-y divide-white/8 border-y border-white/8">
                        @foreach (trans('register_intro.pillars') as $pillar)
                            <div class="py-4">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-100">{{ $pillar['label'] }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-stone-50">{{ $pillar['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-stone-400">{{ $pillar['body'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <p class="rs-label">{{ __('register_intro.process_title') }}</p>
                        <ol class="mt-3 grid gap-2">
                            @foreach (trans('register_intro.steps') as $index => $step)
                                <li class="flex items-center gap-3 text-sm leading-6 text-stone-300">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-amber-200/20 bg-amber-200/10 text-xs font-bold text-amber-50">
                                        {{ $index + 1 }}
                                    </span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </aside>
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
