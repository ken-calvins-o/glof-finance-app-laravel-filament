{{--
    Fund balances.

    A plain bar list rather than a chart library: it needs no JavaScript, reads
    correctly on a phone, and puts the fund name, the amount and the relative
    size on one line each — which is exactly the comparison being made.
--}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Money held per fund</x-slot>

        <x-slot name="description">
            What members have contributed into each fund since the group started.
        </x-slot>

        <x-slot name="headerEnd">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $this->getGrandTotal() }}
            </span>
        </x-slot>

        @php($funds = $this->getFunds())

        @if ($funds->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No funds have been set up yet.
            </p>
        @else
            <ul class="space-y-4">
                @foreach ($funds as $fund)
                    <li>
                        <div class="flex items-baseline justify-between gap-4">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $fund['name'] }}
                            </span>
                            <span class="text-sm tabular-nums text-gray-600 dark:text-gray-300">
                                {{ $fund['formatted'] }}
                            </span>
                        </div>

                        <div
                            class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                            role="img"
                            aria-label="{{ $fund['name'] }}: {{ $fund['formatted'] }}"
                        >
                            <div
                                class="h-full rounded-full bg-primary-500"
                                style="width: {{ max($fund['share'], 1) }}%"
                            ></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
