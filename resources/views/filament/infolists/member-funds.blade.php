{{-- What this member has put into each fund, largest first. --}}
@php($max = $funds->max('raw') ?: 1)

@if ($funds->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">
        This member has not contributed to any fund yet.
    </p>
@else
    <ul class="space-y-4">
        @foreach ($funds as $fund)
            <li>
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $fund['name'] }}</span>
                    <span class="text-sm tabular-nums text-gray-600 dark:text-gray-300">{{ $fund['amount'] }}</span>
                </div>
                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div
                        class="h-full rounded-full bg-primary-500"
                        style="width: {{ max(round(($fund['raw'] / $max) * 100), 1) }}%"
                    ></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
