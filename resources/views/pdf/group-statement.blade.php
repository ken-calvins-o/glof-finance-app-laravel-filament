{{--
    Group statement, for printing.

    The data now arrives from the page rather than being recomputed here by
    instantiating the page class a second time, and amounts are right-aligned
    so the columns read as money on paper too.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Group Statement</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 2px;
            color: #0f766e;
        }

        .header p {
            font-size: 9px;
            margin: 0;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            font-size: 9px;
        }

        thead th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            font-size: 8px;
            letter-spacing: 0.04em;
        }

        /* Names read left; money reads right. */
        th.name, td.name {
            text-align: left;
        }

        th.amount, td.amount {
            text-align: right;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tfoot td, tfoot th {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        .owing {
            color: #be123c;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

@php use App\Support\Money; @endphp

<div class="header">
    <h1>{{ config('app.name', 'Glof Finance') }} — Group Statement</h1>
    <p>
        As at {{ now()->format('j F Y, g:i A') }}.
        All amounts in Kenyan Shillings (KES), rounded to the nearest 5 cents.
    </p>
</div>

<table>
    <thead>
    <tr>
        <th class="name">Member</th>
        @foreach ($accounts as $account)
            <th class="amount">{{ $account->name }}</th>
        @endforeach
        <th class="amount">Joining fee</th>
        <th class="amount">Loan owing</th>
        <th class="amount">Savings</th>
        <th class="amount">Net worth</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($rows as $row)
        <tr>
            <td class="name">{{ $row['name'] }}</td>
            @foreach ($accounts as $account)
                <td class="amount">{{ Money::format05($row['funds'][$account->id] ?? 0) }}</td>
            @endforeach
            <td class="amount">{{ Money::format05($row['registration_fee']) }}</td>
            <td class="amount {{ $row['loan'] > 0 ? 'owing' : '' }}">{{ Money::format05($row['loan']) }}</td>
            <td class="amount">{{ Money::format05($row['savings']) }}</td>
            <td class="amount">{{ Money::format05($row['net_worth']) }}</td>
        </tr>
    @endforeach
    </tbody>

    <tfoot>
    <tr>
        <th class="name">Group total</th>
        @foreach ($accounts as $account)
            <td class="amount">{{ Money::format05($totals['funds'][$account->id] ?? 0) }}</td>
        @endforeach
        <td class="amount">{{ Money::format05($totals['registration_fee']) }}</td>
        <td class="amount">{{ Money::format05($totals['loan']) }}</td>
        <td class="amount">{{ Money::format05($totals['savings']) }}</td>
        <td class="amount">{{ Money::format05($totals['net_worth']) }}</td>
    </tr>
    </tfoot>
</table>

<div class="footer">
    Generated {{ now()->format('j F Y, g:i A') }} by {{ auth()->user()?->name ?? 'the system' }}
</div>

</body>
</html>
