@props([
    'slug',
    'icon',
    'name',
    'description',
])

<article
    class="ob-companion-chip"
    data-summary-companion="{{ $slug }}"
>
    <div class="flex items-center gap-3">
        <span class="text-2xl leading-none">{{ $icon }}</span>
        <div>
            <p class="font-semibold text-stone-900">{{ $name }}</p>
            <p class="text-sm text-stone-600">{{ $description }}</p>
        </div>
    </div>
</article>
