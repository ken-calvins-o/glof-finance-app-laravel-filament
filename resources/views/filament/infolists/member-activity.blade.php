{{-- The last few payments recorded against this member. --}}
@if ($entries->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">
        No payments have been recorded for this member yet.
    </p>
@else
    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
        @foreach ($entries as $entry)
            @php($isArrears = (float) $entry->amount_contributed < 0)
            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $entry->account?->name ?? 'Unknown fund' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $entry->created_at?->format('j M Y') }}
                        @if ($entry->payment_method)
                            · {{ $entry->payment_method instanceof \App\Enums\PaymentMode
                                    ? $entry->payment_method->getLabel()
                                    : $entry->payment_method }}
                        @endif
                    </p>
                </div>

                <span @class([
                    'text-sm font-semibold tabular-nums',
                    'text-danger-600 dark:text-danger-400' => $isArrears,
                    'text-gray-950 dark:text-white' => ! $isArrears,
                ])>
                    {{ \App\Support\Money::kes($entry->amount_contributed) }}
                    @if ($isArrears)
                        <span class="block text-xs font-normal text-gray-500">arrears</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif
