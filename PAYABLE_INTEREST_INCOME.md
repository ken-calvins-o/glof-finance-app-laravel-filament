# Payable Interest Income Recording

> NOTE (2026-03-04): This feature has been **removed**. Payables no longer add 1% interest on shortfalls, and no `Income` record with origin "Payable Interest" is created.

## Overview

When creating payable records, users who do not have sufficient funds in their account collections will incur debt. **Debt is recorded as the principal shortfall only**.

## Implementation Details

### Feature Description

When a payable is processed with `from_savings = false`:

1. **Shortfall Calculation**: If the user's account collection amount is less than the payable total amount, a shortfall is calculated
2. **Debt Creation**: The outstanding balance (**shortfall only**) is added to the user's debt
3. **No payable-interest income**: No additional income record is created as part of payable processing

### Key Components

#### Modified File: `CreatePayable.php`

- Uses `calculateDeductionAndOutstandingBalance()`
- No longer calls any method that records payable interest into `Income`

## Usage Example

### Scenario 1: User Incurs Debt

**Given:**
- Payable Amount: Kes 1,000
- User's Account Collection: Kes 500
- Shortfall: Kes 500

**Calculation:**
```
Shortfall = 1000 - 500 = 500
Deduction = 1000
Outstanding Balance (Debt) = 500
```

**Result:**
1. Debt record created with `outstanding_balance = 500.00`
2. No "Payable Interest" income record is created

### Scenario 2: User Has Sufficient Funds

**Given:**
- Payable Amount: Kes 1,000
- User's Account Collection: Kes 2,000

**Result:**
- No debt created
- No income record created
- Account collection reduced by Kes 1,000

## Testing

Tests exist in `tests/Feature/PayableInterestIncomeTest.php` to verify:
- Debt is principal shortfall only
- No income record with origin "Payable Interest" is created
