<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Statement</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.2);
            font-weight: bold;
            text-transform: uppercase;
            z-index: -1;
            white-space: nowrap;
        }

        .header {
            margin-bottom: 20px;
            text-align: center;
        }

        .header h2 {
            font-size: 16px;
            margin: 0;
            text-transform: uppercase;
        }

        .header p {
            font-size: 12px;
            margin: 0;
            color: #666;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        .table th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
<!-- Watermark -->
<div class="watermark">GULF</div>

<div class="header">
    <h2>Gulf Group Statement</h2>
    <p>Date: {{ now()->format('F j, Y g:i A') }}</p>
    <p>Monetary values are provided in Kenyan Shillings (KES).</p>
</div>

@php
    // Fetch data and accounts:
    [$tableData, $accounts] = (new \App\Filament\Pages\StaticReadOnlyTable)->getTableData();

    // Initialize and compute totals:
    $totals = collect();

    foreach ($tableData as $row) {
        foreach ($row as $column => $value) {
            if (is_numeric($value)) {
                $totals[$column] = ($totals[$column] ?? 0) + $value;
            }
        }
    }
@endphp

<table class="table">
    <thead>
    <tr>
        <th>Member Name</th>
        <th>Registration Fee</th>
        @foreach ($accounts as $account)
            <th>{{ $account->name }}</th>
        @endforeach
        <th>Loan</th>
        <th>Savings</th>
        <th>Net Worth</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($tableData as $row)
        <tr>
            <td>{{ $row['User'] }}</td>
            <td>{{ number_format($row['Registration Fee'] ?? 0, 2) }}</td>
            @foreach ($accounts as $account)
                <td>{{ number_format($row[$account->name] ?? 0, 2) }}</td>
            @endforeach
            <td>{{ number_format($row['Loan'] ?? 0, 2) }}</td>
            <td>{{ number_format($row['Savings'] ?? 0, 2) }}</td>
            <td>{{ number_format($row['Net Worth'] ?? 0, 2) }}</td>
        </tr>
    @endforeach
    </tbody>

    <!-- Totals Row -->
    <tfoot>
    <tr>
        <th>Totals</th>
        <td>
            <strong>
                {{ number_format($totals['Registration Fee'] ?? 0, 2) }}
            </strong>
        </td>
        @foreach ($accounts as $account)
            <td><strong>{{ number_format($totals[$account->name] ?? 0, 2) }}</strong></td>
        @endforeach
        <td><strong>{{ number_format($totals['Loan'] ?? 0, 2) }}</strong></td>
        <td><strong>{{ number_format($totals['Savings'] ?? 0, 2) }}</strong></td>
        <td><strong>{{ number_format($totals['Net Worth'] ?? 0, 2) }}</strong></td>
    </tr>
    </tfoot>
</table>

<div class="footer">
    <p>Generated on {{ now()->format('F j, Y g:i A') }} by {{auth()->user()->name}} | Glof Group Statement </p>
</div>
</body>
</html>
