@php
    use App\Support\Money;

    // Built once by the page and handed straight to the view. The previous
    // version instantiated the page class again from inside the template to
    // recompute the totals, running every query twice per page load.
    ['rows' => $rows, 'accounts' => $accounts, 'totals' => $totals] = $this->getStatement();

    $numericCell = 'whitespace-nowrap px-4 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300';
    $headerCell = 'whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-end text-gray-500 dark:text-gray-400';
    $footerCell = 'whitespace-nowrap px-4 py-3 text-sm font-semibold text-end tabular-nums text-gray-950 dark:text-white';
@endphp

<x-filament-panels::page>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-filament::input.wrapper
            prefix-icon="heroicon-o-magnifying-glass"
            class="w-full sm:max-w-xs"
        >
            <x-filament::input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Find a member"
            />
        </x-filament::input.wrapper>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $rows->count() }} {{ Str::plural('member', $rows->count()) }}
            &middot; as at {{ now()->format('j M Y, g:ia') }}
        </p>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="max-h-[70vh] overflow-auto">
            <table class="w-full">
                <thead class="sticky top-0 z-20 bg-gray-50 dark:bg-gray-800">
                    <tr>
                        {{--
                            The member column is pinned so a name never scrolls
                            out of view while you are reading across the funds.
                        --}}
                        <th
                            scope="col"
                            class="sticky left-0 z-30 whitespace-nowrap bg-gray-50 px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                        >
                            Member
                        </th>

                        @foreach ($accounts as $account)
                            <th scope="col" class="{{ $headerCell }}">{{ $account->name }}</th>
                        @endforeach

                        <th scope="col" class="{{ $headerCell }}">Joining fee</th>
                        <th scope="col" class="{{ $headerCell }}">Loan owing</th>
                        <th scope="col" class="{{ $headerCell }}">Savings</th>
                        <th scope="col" class="{{ $headerCell }}">Net worth</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($rows as $row)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-white/5">
                            <th
                                scope="row"
                                class="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-3 text-start group-hover:bg-gray-50 dark:bg-gray-900 dark:group-hover:bg-gray-800"
                            >
                                <a
                                    href="{{ \App\Filament\Resources\UserResource::getUrl('view', ['record' => $row['id']]) }}"
                                    class="flex items-center gap-2 text-sm font-medium text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                >
                                    <img src="{{ $row['avatar'] }}" alt="" class="h-6 w-6 shrink-0 rounded-full" />
                                    {{ $row['name'] }}
                                </a>
                            </th>

                            @foreach ($accounts as $account)
                                <td class="{{ $numericCell }}">
                                    {{ Money::format05($row['funds'][$account->id] ?? 0) }}
                                </td>
                            @endforeach

                            <td class="{{ $numericCell }}">{{ Money::format05($row['registration_fee']) }}</td>

                            <td @class([
                                $numericCell,
                                '!text-danger-600 dark:!text-danger-400 font-medium' => $row['loan'] > 0,
                            ])>
                                {{ Money::format05($row['loan']) }}
                            </td>

                            <td class="{{ $numericCell }}">{{ Money::format05($row['savings']) }}</td>

                            <td class="{{ $numericCell }} font-medium !text-gray-950 dark:!text-white">
                                {{ Money::format05($row['net_worth']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $accounts->count() + 5 }}" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-950 dark:text-white">No members found</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if (filled($search))
                                        Nothing matches “{{ $search }}”.
                                    @else
                                        Add members to the group and their positions will appear here.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($rows->isNotEmpty())
                    <tfoot class="sticky bottom-0 z-20 bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                scope="row"
                                class="sticky left-0 z-30 whitespace-nowrap bg-gray-50 px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                Group total
                            </th>

                            @foreach ($accounts as $account)
                                <td class="{{ $footerCell }}">
                                    {{ Money::format05($totals['funds'][$account->id] ?? 0) }}
                                </td>
                            @endforeach

                            <td class="{{ $footerCell }}">{{ Money::format05($totals['registration_fee']) }}</td>
                            <td class="{{ $footerCell }}">{{ Money::format05($totals['loan']) }}</td>
                            <td class="{{ $footerCell }}">{{ Money::format05($totals['savings']) }}</td>
                            <td class="{{ $footerCell }}">{{ Money::format05($totals['net_worth']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Amounts are rounded to the nearest 5 cents. Click a member to open their full statement.
    </p>
</x-filament-panels::page>
