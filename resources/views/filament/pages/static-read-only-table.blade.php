@php
    use App\Support\Money;

    [$tableData, $accounts] = (new \App\Filament\Pages\StaticReadOnlyTable)->getTableData();

    // Initialize a totals array to store dynamic column totals
    $totals = collect();

    foreach ($tableData as $row) {
        foreach ($row as $column => $value) {
            // Check if the value is numeric before summing
            if (is_numeric($value)) {
                $totals[$column] = ($totals[$column] ?? 0) + (float) $value;
            }
        }
    }
@endphp

<x-filament::page>
    <!-- Display current date and time -->
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-amber-600">
            Date and Time: {{ now()->format('F j, Y g:i A') }}
        </h3>
    </div>

    <!-- Table Container -->
    <div class="border border-gray-300 bg-white rounded-lg shadow-md">
        <div class="max-h-[500px] overflow-y-auto overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <!-- Table Header -->
                <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Member Name
                    </th>
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Registration Fee
                    </th>
                    @foreach ($accounts as $account)
                        <th style="width: 150px;"
                            class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                            {{ $account->name }}
                        </th>
                    @endforeach
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Loan
                    </th>
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Savings
                    </th>
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Net Worth
                    </th>
                </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($tableData as $row)
                    <tr class="hover:bg-gray-50 even:bg-gray-50 odd:bg-white">
                        <!-- Display the user name -->
                        <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <span>{{ $row['User'] }}</span>
                        </td>
                        <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ Money::format05($row['Registration Fee'] ?? 0) }}
                        </td>
                        @foreach ($accounts as $account)
                            <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Kes. {{ Money::format05($row[$account->name] ?? 0) }}
                            </td>
                        @endforeach
                        <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ Money::format05($row['Loan'] ?? 0) }}
                        </td>
                        <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ Money::format05($row['Savings'] ?? 0) }}
                        </td>
                        <td style="width: 150px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ Money::format05($row['Net Worth'] ?? 0) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>

                <!-- Totals Row -->
                <tfoot>
                <tr class="bg-gray-100">
                    <th style="width: 150px;"
                        class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase border-gray-300">
                        Totals
                    </th>
                    <th style="width: 150px;" class="px-6 py-3 text-left text-sm text-gray-700 border-gray-300">
                        {{ Money::format05($totals['Registration Fee'] ?? 0) }}
                    </th>
                    @foreach ($accounts as $account)
                        <th style="width: 150px;" class="px-6 py-3 text-left text-sm text-gray-700 border-gray-300">
                            {{ Money::format05($totals[$account->name] ?? 0) }}
                        </th>
                    @endforeach
                    <th style="width: 150px;" class="px-6 py-3 text-left text-sm text-gray-700 border-gray-300">
                        {{ Money::format05($totals['Loan'] ?? 0) }}
                    </th>
                    <th style="width: 150px;" class="px-6 py-3 text-left text-sm text-gray-700 border-gray-300">
                        {{ Money::format05($totals['Savings'] ?? 0) }}
                    </th>
                    <th style="width: 150px;" class="px-6 py-3 text-left text-sm text-gray-700 border-gray-300">
                        {{ Money::format05($totals['Net Worth'] ?? 0) }}
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-filament::page>
