@php
    $lang = $lang ?? 'en';
    $t = fn (array $item, ?string $key = null) => $key
        ? ($item[$key.'_'.$lang] ?? $item[$key.'_en'] ?? '')
        : ($item[$lang] ?? $item['en'] ?? '');
@endphp

{{--
    Public user manual.

    Colours follow the landing page exactly: deep space #050609 ground, dashed
    neutral-600/30 rules, electric blue accents. Language is switched
    server-side from a single content source, so the English and Tagalog copy
    can never drift apart.
--}}
<div class="w-full bg-[#050609] min-h-screen font-sans antialiased text-neutral-200">

    {{-- ============ HEADER ============ --}}
    <header class="sticky top-0 z-50 bg-[#050609]/90 backdrop-blur-sm border-b border-dashed border-neutral-600/30">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 shrink-0">
                <x-ui.brand href="{{ route('welcome') }}" logoClass="size-10!" logo="{{ asset('favicon.svg') }}" />
                <span class="text-xl sm:text-2xl font-black text-white">Citi<span class="text-blue-400">POS</span></span>
                <span class="hidden sm:inline text-xs font-mono uppercase tracking-widest text-neutral-500 border border-neutral-600/40 rounded px-2 py-0.5 ml-2">
                    {{ $lang === 'tl' ? 'Gabay' : 'Docs' }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                {{-- The whole manual, A4, in the language currently selected. --}}
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf"
                        class="inline-flex items-center gap-2 rounded-lg border border-neutral-600/40 px-3 py-1.5 text-sm font-semibold text-neutral-300 hover:text-white hover:bg-white/5 hover:border-blue-500/60 transition-colors disabled:opacity-60">
                    <x-ui.icon name="arrow-down-tray" class="size-4 shrink-0" wire:loading.remove wire:target="downloadPdf" />
                    <x-ui.icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="downloadPdf" />
                    <span class="hidden md:inline">{{ $lang === 'tl' ? 'I-download ang PDF' : 'Download PDF' }}</span>
                    <span class="md:hidden">PDF</span>
                </button>

                {{-- Language switch --}}
                <div class="flex items-center rounded-lg border border-neutral-600/40 overflow-hidden text-sm" role="group" aria-label="Language">
                    <button type="button" wire:click="setLang('en')"
                        @class([
                            'px-3 py-1.5 font-semibold transition-colors',
                            'bg-blue-600 text-white' => $lang === 'en',
                            'text-neutral-400 hover:text-white hover:bg-white/5' => $lang !== 'en',
                        ])>English</button>
                    <button type="button" wire:click="setLang('tl')"
                        @class([
                            'px-3 py-1.5 font-semibold transition-colors',
                            'bg-blue-600 text-white' => $lang === 'tl',
                            'text-neutral-400 hover:text-white hover:bg-white/5' => $lang !== 'tl',
                        ])>Tagalog</button>
                </div>

                @guest
                    <x-ui.button href="{{ route('login') }}" class="bg-blue-600! hover:bg-blue-500! text-white! hidden sm:inline-flex" icon-after="arrow-right">
                        {{ $lang === 'tl' ? 'Mag-sign In' : 'Sign In' }}
                    </x-ui.button>
                @endguest
                @auth
                    <x-ui.button href="{{ route('home') }}" class="bg-blue-600! hover:bg-blue-500! text-white! hidden sm:inline-flex" icon-after="arrow-right">
                        {{ $lang === 'tl' ? 'Dashboard' : 'Dashboard' }}
                    </x-ui.button>
                @endauth
            </div>
        </div>
    </header>

    {{-- ============ TITLE ============ --}}
    <div class="border-b border-dashed border-neutral-600/30">
        <div class="max-w-7xl mx-auto px-4 py-10 sm:py-14">
            <p class="text-xs font-mono uppercase tracking-[0.2em] text-blue-400 mb-3">
                {{ $lang === 'tl' ? 'Gabay ng Gumagamit' : 'User Manual' }}
            </p>
            <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white max-w-3xl">
                {{ $lang === 'tl' ? 'Paano gamitin ang CitiPOS' : 'How to use CitiPOS' }}
            </h1>
            <p class="mt-4 text-neutral-400 max-w-2xl text-base sm:text-lg">
                {{ $lang === 'tl'
                    ? 'Bawat feature ng POS at inventory, hakbang-hakbang, may totoong screenshot mula mismo sa sistema. Piliin ang seksyon na para sa trabaho mo.'
                    : 'Every POS and inventory feature, step by step, with real screenshots from the system itself. Pick the section that matches your job.' }}
            </p>
        </div>
    </div>

    {{-- ============ BODY ============ --}}
    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-10"
         x-data="{
             /* Switching category swaps the content underneath you, so jump to the
                top of it immediately -- a smooth scroll would still be animating
                while Livewire changes the page height and would land nowhere. */
             toTop() {
                 this.$refs.top?.scrollIntoView({ behavior: 'instant', block: 'start' });
             },
             /* In-page jumps keep the same content, so these can animate. */
             toSection(id) {
                 document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
             },
         }">

        {{-- Category rail --}}
        <nav class="lg:sticky lg:top-28 lg:self-start" aria-label="{{ $lang === 'tl' ? 'Mga seksyon' : 'Sections' }}">
            <p class="text-xs font-mono uppercase tracking-[0.18em] text-neutral-500 mb-3">
                {{ $lang === 'tl' ? 'Mga Seksyon' : 'Sections' }}
            </p>
            <div class="flex flex-col gap-1.5">
                @foreach($this->categories as $category)
                    <button type="button" wire:click="setCategory('{{ $category['id'] }}')" x-on:click="toTop()"
                        @class([
                            'text-left rounded-xl border px-4 py-3 transition-all group',
                            'border-blue-500/60 bg-blue-500/10' => $this->category === $category['id'],
                            'border-neutral-600/30 hover:border-neutral-500/60 hover:bg-white/5' => $this->category !== $category['id'],
                        ])>
                        <span class="flex items-center gap-2.5">
                            <x-ui.icon :name="$category['icon']" @class([
                                'size-5 shrink-0',
                                'text-blue-400' => $this->category === $category['id'],
                                'text-neutral-500 group-hover:text-neutral-300' => $this->category !== $category['id'],
                            ]) />
                            <span @class([
                                'font-bold text-sm',
                                'text-white' => $this->category === $category['id'],
                                'text-neutral-300' => $this->category !== $category['id'],
                            ])>{{ $t($category) }}</span>
                        </span>
                        <span class="block text-xs text-neutral-500 mt-1.5 leading-relaxed">
                            {{ $t($category, 'blurb') }}
                        </span>
                    </button>
                @endforeach
            </div>
        </nav>

        {{-- Content --}}
        <main class="min-w-0">
            <span x-ref="top" class="block scroll-mt-28" aria-hidden="true"></span>

            @if($this->activeCategory)
                <div class="mb-8 pb-6 border-b border-dashed border-neutral-600/30">
                    <h2 class="text-2xl sm:text-3xl font-black text-white">{{ $t($this->activeCategory) }}</h2>
                    <p class="text-neutral-400 mt-2">{{ $t($this->activeCategory, 'blurb') }}</p>
                </div>
            @endif

            {{-- Section jump links --}}
            @if(count($this->sections) > 1)
                <div class="flex flex-wrap gap-2 mb-10">
                    @foreach($this->sections as $i => $section)
                        <a href="#{{ $section['id'] }}" x-on:click.prevent="toSection('{{ $section['id'] }}')"
                           class="text-xs font-medium rounded-full border border-neutral-600/40 px-3 py-1.5 text-neutral-400 hover:text-white hover:border-blue-500/60 hover:bg-blue-500/10 transition-colors">
                            {{ $i + 1 }}. {{ $t($section) }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-col gap-14">
                @foreach($this->sections as $i => $section)
                    <section id="{{ $section['id'] }}" class="scroll-mt-28">
                        <div class="flex items-baseline gap-3 mb-5">
                            <span class="text-sm font-mono font-bold text-blue-400">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="text-xl sm:text-2xl font-bold text-white">{{ $t($section) }}</h3>
                        </div>

                        <div class="flex flex-col gap-5">
                            @foreach($section['blocks'] as $block)
                                @switch($block['type'])

                                    @case('text')
                                        <p class="text-neutral-300 leading-relaxed max-w-3xl">{!! $t($block) !!}</p>
                                        @break

                                    @case('steps')
                                        <ol class="flex flex-col gap-3 max-w-3xl">
                                            @foreach($block['items'] as $n => $item)
                                                <li class="flex gap-3.5">
                                                    <span class="shrink-0 size-6 rounded-full bg-blue-500/15 border border-blue-500/50 text-blue-300 text-xs font-bold font-mono grid place-items-center mt-0.5">
                                                        {{ $n + 1 }}
                                                    </span>
                                                    <span class="text-neutral-300 leading-relaxed">{!! $t($item) !!}</span>
                                                </li>
                                            @endforeach
                                        </ol>
                                        @break

                                    @case('list')
                                        <ul class="flex flex-col gap-2.5 max-w-3xl">
                                            @foreach($block['items'] as $item)
                                                <li class="flex gap-3">
                                                    <span class="shrink-0 mt-2 size-1.5 rounded-full bg-blue-400"></span>
                                                    <span class="text-neutral-300 leading-relaxed">{!! $t($item) !!}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @break

                                    @case('note')
                                        <div class="max-w-3xl rounded-xl border border-blue-500/40 bg-blue-500/10 px-4 py-3.5 flex gap-3">
                                            <x-ui.icon name="information-circle" class="size-5 text-blue-400 shrink-0 mt-0.5" />
                                            <div>
                                                <p class="text-[10px] font-mono font-bold uppercase tracking-[0.14em] text-blue-400 mb-1">
                                                    {{ $lang === 'tl' ? 'Tandaan' : 'Note' }}
                                                </p>
                                                <p class="text-neutral-300 text-sm leading-relaxed">{!! $t($block) !!}</p>
                                            </div>
                                        </div>
                                        @break

                                    @case('warn')
                                        <div class="max-w-3xl rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3.5 flex gap-3">
                                            <x-ui.icon name="exclamation-triangle" class="size-5 text-amber-400 shrink-0 mt-0.5" />
                                            <div>
                                                <p class="text-[10px] font-mono font-bold uppercase tracking-[0.14em] text-amber-400 mb-1">
                                                    {{ $lang === 'tl' ? 'Mag-ingat' : 'Careful' }}
                                                </p>
                                                <p class="text-neutral-300 text-sm leading-relaxed">{!! $t($block) !!}</p>
                                            </div>
                                        </div>
                                        @break

                                    @case('image')
                                        <figure class="max-w-4xl">
                                            <div class="rounded-xl overflow-hidden border border-neutral-600/40 bg-[#0a1331]">
                                                <img src="{{ asset('docs/img/'.$block['src']) }}"
                                                     alt="{{ strip_tags($t($block)) }}"
                                                     loading="lazy" decoding="async"
                                                     class="w-full h-auto block" />
                                            </div>
                                            <figcaption class="text-xs text-neutral-500 mt-2.5 leading-relaxed">
                                                {!! $t($block) !!}
                                            </figcaption>
                                        </figure>
                                        @break

                                    @case('table')
                                        <div class="max-w-4xl overflow-x-auto rounded-xl border border-neutral-600/40">
                                            <table class="w-full text-sm text-left">
                                                <thead class="bg-white/5 border-b border-neutral-600/40">
                                                    <tr>
                                                        @foreach($block['head'] as $cell)
                                                            <th class="px-4 py-3 text-[10px] font-mono uppercase tracking-[0.12em] text-neutral-400 font-bold whitespace-nowrap">
                                                                {{ $t($cell) }}
                                                            </th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-neutral-600/30">
                                                    @foreach($block['rows'] as $row)
                                                        <tr class="hover:bg-white/5 transition-colors">
                                                            @foreach($row as $cell)
                                                                <td class="px-4 py-3 text-neutral-300 align-top">{!! $t($cell) !!}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @break

                                @endswitch
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Next category --}}
            @php
                $ids = array_column($this->categories, 'id');
                $pos = array_search($this->category, $ids, true);
                $next = $pos !== false && isset($this->categories[$pos + 1]) ? $this->categories[$pos + 1] : null;
            @endphp
            @if($next)
                <div class="mt-16 pt-8 border-t border-dashed border-neutral-600/30">
                    <button type="button" wire:click="setCategory('{{ $next['id'] }}')" x-on:click="toTop()"
                            class="w-full sm:w-auto text-left rounded-xl border border-neutral-600/30 hover:border-blue-500/60 hover:bg-blue-500/10 px-5 py-4 transition-all group">
                        <span class="block text-xs font-mono uppercase tracking-[0.14em] text-neutral-500 mb-1">
                            {{ $lang === 'tl' ? 'Susunod' : 'Next' }}
                        </span>
                        <span class="flex items-center gap-2 text-white font-bold">
                            {{ $t($next) }}
                            <x-ui.icon name="arrow-right" class="size-4 text-blue-400 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </div>
            @endif
        </main>
    </div>

    {{-- ============ PRINT CALLOUT ============ --}}
    <div class="max-w-7xl mx-auto px-4 pb-12">
        <div class="rounded-2xl border border-dashed border-neutral-600/40 bg-white/[0.02] px-6 py-7 sm:flex sm:items-center sm:justify-between gap-6">
            <div class="min-w-0">
                <h3 class="text-white font-bold text-lg">
                    {{ $lang === 'tl' ? 'Kailangan ng kopyang naka-print?' : 'Need a copy for the counter?' }}
                </h3>
                <p class="text-neutral-400 text-sm mt-1.5 max-w-xl">
                    {{ $lang === 'tl'
                        ? 'Ang buong gabay bilang isang A4 na PDF — lahat ng kabanata, screenshot at reference, sa wikang nakapili ngayon. Pang-print o pang-share offline.'
                        : 'The entire manual as one A4 PDF — every chapter, screenshot and reference page, in the language selected above. Print it or share it offline.' }}
                </p>
            </div>
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf"
                    class="mt-4 sm:mt-0 shrink-0 inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-500 px-5 py-2.5 font-semibold text-white transition-colors disabled:opacity-60">
                <x-ui.icon name="arrow-down-tray" class="size-4 shrink-0" wire:loading.remove wire:target="downloadPdf" />
                <x-ui.icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="downloadPdf" />
                <span wire:loading.remove wire:target="downloadPdf">{{ $lang === 'tl' ? 'I-download ang PDF' : 'Download PDF' }}</span>
                <span wire:loading wire:target="downloadPdf">{{ $lang === 'tl' ? 'Inihahanda...' : 'Preparing...' }}</span>
            </button>
        </div>
    </div>

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t border-dashed border-neutral-600/30 mt-10">
        <div class="max-w-7xl mx-auto px-4 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-neutral-500 text-center sm:text-left">
                {{ $lang === 'tl'
                    ? 'Kung may screen na hindi tugma sa gabay na ito, ang gabay ang mali — ipaalam ito para maitama.'
                    : 'If a screen stops matching this guide, the guide is what is wrong — report it so it can be corrected.' }}
            </p>
            <a href="{{ route('welcome') }}" wire:navigate class="text-sm text-blue-400 hover:text-blue-300 font-semibold whitespace-nowrap">
                {{ $lang === 'tl' ? 'Bumalik sa home' : 'Back to home' }} &rarr;
            </a>
        </div>
    </footer>
</div>
