@php
    [$tableData, $accounts] = (new \App\Filament\Pages\StaticReadOnlyTable)->getTableData();

    // Initialize a totals array to store dynamic column totals
    $totals = [];

    foreach ($tableData as $row) {
        foreach ($row as $column => $value) {
            // Check if the value is numeric before summing
            if (is_numeric($value)) {
                if (!isset($totals[$column])) {
                    $totals[$column] = 0; // Initialize total if it doesn't exist
                }
                $totals[$column] += $value; // Add the value to the appropriate column total
            }
        }
    }
@endphp

<x-filament::page>
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-amber-600">
            Date and Time: {{ now()->format('F j, Y g:i A') }}
        </h3>
    </div>

    <div class="border border-gray-300 bg-white rounded-lg shadow-md">
        <div class="max-h-[500px] overflow-y-auto overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Member Name
                    </th>
                    @foreach ($accounts as $account)
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                            {{ $account->name }}
                        </th>
                    @endforeach
                    <th class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Loan
                    </th>
                    <th class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Savings
                    </th>
                    <th class="px-6 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-gray-300">
                        Net Worth
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($tableData as $row)
                    <tr class="hover:bg-gray-50 even:bg-gray-50 odd:bg-white">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $row['User'] }}
                        </td>
                        @foreach ($accounts as $account)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Kes. {{ number_format($row['Account ' . $account->id], 2) }}
                            </td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ number_format($row['Loan'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ number_format($row['Savings'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            Kes. {{ number_format($row['Net Worth'], 2) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>

