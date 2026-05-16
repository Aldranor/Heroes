<x-layouts::app title="Hall des échos">
    @php
        $selectedScene = $selectedMemoryScene;
        $memoryCount = $memoryScenes->count();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-stone-950 p-4 sm:p-6">
            <img src="{{ asset('images/backgrounds/set3_1_0.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/30 via-slate-950/74 to-stone-950/94"></div>

            <div class="relative">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-100/90">Archives vivantes</p>
                <h1 class="mt-3 text-3xl font-semibold leading-tight text-stone-50 sm:text-5xl">Hall des échos</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-stone-300">Retrouve les scènes déjà vécues et relis les dialogues marquants de l académie.</p>
                <div class="mt-5 inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-stone-100">
                    {{ $memoryCount }} souvenir{{ $memoryCount > 1 ? 's' : '' }} déverrouillé{{ $memoryCount > 1 ? 's' : '' }}
                </div>
            </div>
        </section>

        @if ($memoryScenes->isEmpty())
            <section class="rounded-[1.8rem] border border-white/10 bg-stone-950/80 p-5 text-sm text-stone-200">
                Aucun souvenir n est encore archivé.
            </section>
        @else
            <section
                x-data="{
                    lines: JSON.parse($el.dataset.sceneLines || '[]'),
                    index: 0,
                    get currentLine() {
                        return this.lines[this.index] ?? { speaker: 'narrator', name: 'Narration', text: '' };
                    },
                    get isLastLine() {
                        return this.index >= this.lines.length - 1;
                    },
                    speakerLabel(line = this.currentLine) {
                        if (line.speaker === 'hero') return 'Héros';
                        if (line.speaker === 'npc') return line.name || 'Allié';
                        return line.name || 'Narration';
                    },
                    advance() {
                        if (! this.isLastLine) this.index += 1;
                    },
                    restart() {
                        this.index = 0;
                    },
                }"
                data-scene-lines="{{ collect($selectedScene['lines'] ?? [])->toJson() }}"
                class="rounded-[2rem] border border-white/10 bg-slate-950/94 p-4 sm:p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">{{ $selectedScene['title'] ?? 'Souvenir' }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-stone-50">Relecture</h2>
                        @if ($selectedMemoryMeta)
                            <p class="mt-2 text-sm text-stone-300">{{ $selectedMemoryMeta['source'] }}</p>
                        @endif
                    </div>
                    <button type="button" @click="restart()" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-100">
                        Rejouer
                    </button>
                </div>

                <div class="mt-4 rounded-[1.6rem] border border-white/10 bg-[linear-gradient(180deg,rgba(8,15,33,0.96)_0%,rgba(19,32,55,0.98)_100%)] p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-100" x-text="speakerLabel()"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-300">
                            <span x-text="index + 1"></span>/<span x-text="lines.length"></span>
                        </span>
                    </div>

                    <p class="mt-4 min-h-[4rem] text-[15px] leading-6 text-stone-50 sm:text-base sm:leading-7" x-text="currentLine.text"></p>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <template x-for="(line, lineIndex) in lines" :key="lineIndex">
                                <span class="h-2.5 w-2.5 rounded-full transition" :class="lineIndex <= index ? 'bg-violet-300' : 'bg-white/18'"></span>
                            </template>
                        </div>

                        <button type="button" @click="advance()" :disabled="isLastLine" class="rounded-full bg-violet-300 px-4 py-2.5 text-sm font-bold uppercase tracking-[0.16em] text-stone-950 disabled:cursor-not-allowed disabled:bg-stone-700 disabled:text-stone-400">
                            Suivant
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-3">
                @foreach ($memoryScenes as $memory)
                    <a
                        href="{{ route('echoes.index', ['scene_type' => $memory['scene_type'], 'scene_id' => $memory['scene_id']]) }}"
                        class="rounded-[1.5rem] border p-4 transition {{ ($selectedMemoryMeta['scene_type'] ?? null) === $memory['scene_type'] && ($selectedMemoryMeta['scene_id'] ?? null) === $memory['scene_id'] ? 'border-violet-200/30 bg-violet-300/10' : 'border-white/10 bg-stone-950/70 hover:border-white/20' }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-400">{{ $memory['source'] ?: 'Archives' }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-stone-50">{{ $memory['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-stone-300">{{ $memory['excerpt'] }}</p>
                            </div>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold text-stone-100">
                                {{ optional($memory['completed_at'])->format('d/m/Y') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </section>
        @endif
    </div>
</x-layouts::app>
