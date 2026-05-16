@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'layer',
])

<fieldset class="space-y-3">
    <legend class="text-sm font-semibold tracking-[0.01em] text-stone-800">{{ $label }}</legend>

    <div class="grid grid-cols-4 gap-3 sm:grid-cols-5">
        @foreach ($options as $option)
            @php
                $id = str($name.'-'.$option['key'])->slug('-');
            @endphp

            <label for="{{ $id }}" class="cursor-pointer">
                <input
                    id="{{ $id }}"
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $option['value'] }}"
                    class="peer sr-only"
                    data-avatar-layer="{{ $layer }}"
                    data-avatar-key="{{ $option['key'] }}"
                    @checked($selected === $option['value'])
                >
                <span class="ob-swatch" style="--swatch-color: {{ $option['swatch'] ?? $option['value'] }};">
                    <span class="sr-only">{{ $option['label'] }}</span>
                </span>
            </label>
        @endforeach
    </div>
</fieldset>
