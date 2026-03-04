# Implementation Summary: Payable Interest Income Recording

> NOTE (2026-03-04): The payable-interest feature has been **removed**.
> Payable shortfalls create debt on the **principal shortfall only** and do **not** create an `Income` row with origin "Payable Interest".

## Current Behavior

### Payable processing (`from_savings = false`)

- Deduction from account collection is the payable principal amount.
- If the user's available account collection is insufficient, the debt outstanding balance increases by the **shortfall only**.
- No 1% interest is added at payable creation time.

## Testing

See `tests/Feature/PayableInterestIncomeTest.php` for updated expectations.
