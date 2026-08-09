@php
    // Presented once here rather than inside the loop, so the search index and
    // the markup are built from exactly the same data.
    $sections = $this->getSections()->map(fn (array $section) => [
        ...$section,
        'topics' => collect($section['topics'])->map(fn (array $topic) => $this->present($topic)),
    ]);

    $haystacks = $sections->flatMap(fn (array $section) => $section['topics']->pluck('haystack'))->values();
    $topicCount = $haystacks->count();
@endphp

<x-filament-panels::page>
    <div
        x-data="{
            query: '',
            zoom: null,

            get searching() {
                return this.query.trim().length > 1;
            },

            matches(haystack) {
                if (! this.searching) return true;

                // Every word has to appear somewhere, in any order, so
                // 'repay loan' finds the repayment topic without the reader
                // having to guess our phrasing.
                return this.query
                    .toLowerCase()
                    .split(/\s+/)
                    .filter(Boolean)
                    .every((word) => haystack.includes(word));
            },

            /* Counted from the same index the topics are filtered by, rather
               than by asking the DOM what is currently visible — which would
               depend on Alpine having finished its update first. */
            haystacks: @js($haystacks),

            get found() {
                return this.haystacks.filter((haystack) => this.matches(haystack)).length;
            },

            /* Opening the page on a #topic link should land on that topic
               already open, not on a closed row the reader has to find. */
            openHash() {
                const id = window.location.hash.replace('#', '');
                if (! id) return;

                const topic = this.$root.querySelector(`[data-help-topic][id='${CSS.escape(id)}']`);
                if (! topic) return;

                topic.open = true;
                this.$nextTick(() => topic.scrollIntoView({ block: 'center' }));
            },
        }"
        x-init="$nextTick(() => openHash())"
        @keydown.escape.window="zoom = null"
        class="flex flex-col gap-6"
    >
        {{--
            Search sits above everything and stays there while you scroll. Most
            people arrive at a manual with a word in mind ("repayment",
            "arrears") rather than a section, and making them scroll back up to
            try a second word is what stops people trying a second word.

            top-16 clears Filament's own topbar, which is sticky at top-0, and
            z-10 keeps this underneath it. At z-20 the search box slid over the
            topbar as you scrolled and hid the global search, the Record button
            and the profile menu.
        --}}
        <div class="sticky top-16 z-10 -mx-4 bg-gray-50/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-xl sm:px-4 dark:bg-gray-950/95">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input
                    type="search"
                    x-model.debounce.150ms="query"
                    placeholder="Search — try “repayment”, “arrears”, “add a member”"
                    aria-label="Search the help centre"
                />
            </x-filament::input.wrapper>

            <p
                x-cloak
                x-show="searching"
                class="mt-2 text-sm text-gray-500 dark:text-gray-400"
            >
                <span x-text="found"></span> <span x-text="found === 1 ? 'topic' : 'topics'"></span> match
                “<span x-text="query.trim()" class="font-medium text-gray-950 dark:text-white"></span>”.
            </p>
        </div>

        {{-- Jump links, so the manual can be used as a contents page too. --}}
        <nav
            x-cloak
            x-show="! searching"
            class="flex flex-wrap gap-2"
            aria-label="Help sections"
        >
            @foreach ($sections as $section)
                <a
                    href="#section-{{ $section['id'] }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/5 transition hover:bg-gray-50 hover:text-primary-600 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-800 dark:hover:text-primary-400"
                >
                    <x-filament::icon :icon="$section['icon']" class="h-4 w-4" />
                    {{ $section['title'] }}
                </a>
            @endforeach
        </nav>

        @foreach ($sections as $section)
            @php
                $topics = $section['topics'];
                // A section shows while any one of its topics does. Testing the
                // words of each topic separately, rather than the section's text
                // as one lump, keeps a two-word search from matching a section
                // only because the words appear in two unrelated topics.
                $sectionHaystacks = $topics->pluck('haystack')->values();
            @endphp

            <section
                id="section-{{ $section['id'] }}"
                x-show="@js($sectionHaystacks).some((haystack) => matches(haystack))"
                class="scroll-mt-32"
            >
                <div class="mb-3 flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$section['icon']" class="h-5 w-5" />
                    </span>

                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ $section['title'] }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $section['blurb'] }}
                        </p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/5 dark:bg-gray-900 dark:ring-white/10">
                    @foreach ($topics as $topic)
                        <details
                            data-help-topic
                            id="{{ $topic['id'] }}"
                            x-show="matches(@js($topic['haystack']))"
                            :open="searching"
                            class="group scroll-mt-32"
                        >
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3.5 transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-filament::icon
                                    icon="heroicon-m-chevron-right"
                                    class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-90"
                                />

                                <span class="flex-1 text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $topic['title'] }}
                                </span>

                                @if ($topic['audience'] === \App\Support\HelpTopics::TREASURER)
                                    <x-filament::badge color="gray" size="xs" class="hidden sm:inline-flex">
                                        Treasurer
                                    </x-filament::badge>
                                @endif
                            </summary>

                            <div class="space-y-4 px-4 pb-5 pl-11 pr-4 text-sm text-gray-600 dark:text-gray-400">
                                @if (filled((string) $topic['summary']))
                                    <p>{{ $topic['summary'] }}</p>
                                @endif

                                @if ($topic['steps'])
                                    <ol class="list-decimal space-y-2 pl-5 marker:text-gray-400 marker:tabular-nums">
                                        @foreach ($topic['steps'] as $step)
                                            <li class="pl-1">{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                @endif

                                @if ($topic['image'])
                                    {{--
                                        Shown small and clickable rather than
                                        full width: at reading size a screenshot
                                        is there to confirm "yes, this is the
                                        screen I am on", and anyone who needs to
                                        read the labels in it can tap to enlarge.
                                    --}}
                                    <button
                                        type="button"
                                        @click="zoom = @js($topic['image'])"
                                        class="block w-full overflow-hidden rounded-lg ring-1 ring-gray-950/10 transition hover:ring-primary-500 dark:ring-white/10"
                                    >
                                        <img
                                            src="{{ $topic['image'] }}"
                                            alt="Screenshot: {{ $topic['title'] }}"
                                            loading="lazy"
                                            class="w-full"
                                        />
                                    </button>
                                    <p class="-mt-2 text-xs text-gray-400 dark:text-gray-500">
                                        Tap the picture to see it larger.
                                    </p>
                                @endif

                                @if ($topic['notes'])
                                    <ul class="space-y-2 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                                        @foreach ($topic['notes'] as $note)
                                            <li class="flex gap-2">
                                                <x-filament::icon
                                                    icon="heroicon-m-information-circle"
                                                    class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"
                                                />
                                                <span>{{ $note }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($topic['link'])
                                    <x-filament::button
                                        tag="a"
                                        :href="$topic['link']['url']"
                                        size="sm"
                                        icon="heroicon-m-arrow-right"
                                        icon-position="after"
                                    >
                                        {{ $topic['link']['label'] }}
                                    </x-filament::button>
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Nothing matched. Say so, and give a way out. --}}
        <div
            x-cloak
            x-show="searching && found === 0"
            class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                Nothing here matches “<span x-text="query.trim()"></span>”.
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Try a simpler word — “loan”, “member”, “fund”, “owed”.
            </p>
            <x-filament::button
                color="gray"
                size="sm"
                class="mt-4"
                x-on:click="query = ''"
            >
                Clear the search
            </x-filament::button>
        </div>

        <p class="pb-2 text-center text-xs text-gray-400 dark:text-gray-500">
            {{ $topicCount }} {{ Str::plural('topic', $topicCount) }} &middot;
            Something missing or wrong here? Tell whoever set the group up on this app.
        </p>

        {{-- The enlarged screenshot. --}}
        <div
            x-cloak
            x-show="zoom"
            x-transition.opacity
            @click="zoom = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Enlarged screenshot"
        >
            <img
                :src="zoom"
                alt=""
                class="max-h-full max-w-full rounded-lg shadow-2xl"
            />
            <button
                type="button"
                @click="zoom = null"
                class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20"
                aria-label="Close"
            >
                <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
            </button>
        </div>
    </div>
</x-filament-panels::page>
