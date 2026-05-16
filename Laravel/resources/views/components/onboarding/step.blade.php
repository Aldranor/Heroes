@props([
    'step',
    'eyebrow',
    'title',
    'description',
    'active' => false,
])

<section
    data-onboarding-panel="{{ $step }}"
    @class([
        'ob-step-card rounded-[2rem] border border-stone-200 bg-white p-5 shadow-[0_20px_40px_rgba(15,23,42,0.08)] sm:p-6',
        'hidden' => ! $active,
    ])
>
    <header class="space-y-3">
        <p class="ob-kicker text-stone-500">{{ $eyebrow }}</p>
        <div class="space-y-2">
            <h2 class="rs-display text-4xl leading-none text-stone-950 sm:text-[2.75rem]">{{ $title }}</h2>
            <p class="max-w-2xl text-sm leading-7 text-stone-600 sm:text-base">{{ $description }}</p>
        </div>
    </header>

    <div class="mt-6 space-y-6">
        {{ $slot }}
    </div>
</section>
