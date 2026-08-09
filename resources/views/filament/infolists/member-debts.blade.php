{{-- Outstanding debts, so the "owes the group" figure above can be broken down. --}}
<ul class="divide-y divide-gray-100 dark:divide-gray-800">
    @foreach ($debts as $debt)
        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ $debt->account?->name ?? 'Loan' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Owing since {{ $debt->created_at?->format('j M Y') }}
                    @if ($debt->last_interest_applied_on)
                        · interest last added {{ $debt->last_interest_applied_on->format('j M Y') }}
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-filament::badge :color="$debt->debt_status->getColor()">
                    {{ $debt->debt_status->getLabel() }}
                </x-filament::badge>

                <span class="text-sm font-semibold tabular-nums text-danger-600 dark:text-danger-400">
                    {{ \App\Support\Money::kes($debt->outstanding_balance) }}
                </span>
            </div>
        </li>
    @endforeach
</ul>
