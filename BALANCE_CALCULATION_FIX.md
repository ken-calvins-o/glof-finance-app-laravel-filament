# Balance Calculation Fix - Summary

## Problem Identified

When creating a new savings record, the balance was being **overwritten** instead of **accumulated**.

### Example of the Bug:
- User had a balance of **2000 Kes**
- User deposits **3000 Kes**
- Expected new balance: **5000 Kes** (2000 + 3000)
- Actual new balance: **3000 Kes** ❌ (overwrites instead of adding)

## Root Cause

The `SavingsCalculator::calculateBalance()` method was simply returning the credit amount instead of adding it to the existing balance:

```php
// BEFORE (Wrong)
public function calculateBalance(float $creditAmount): float
{
    return $creditAmount; // This overwrites!
}
```

## Solution Implemented

### 1. Added `getCurrentBalance()` Method
```php
public function getCurrentBalance(int $userId): float
{
    return Saving::where('user_id', $userId)
        ->latest('id')
        ->value('balance') ?? 0;
}
```

### 2. Fixed `calculateBalance()` Method
```php
public function calculateBalance(float $currentBalance, float $creditAmount = 0, float $debitAmount = 0): float
{
    return $currentBalance + $creditAmount - $debitAmount;
}
```

Now it:
- Takes the **current balance**
- **Adds** credit amounts (deposits)
- **Subtracts** debit amounts (withdrawals)

### 3. Updated `getFormDefaults()` Method
```php
public function getFormDefaults(int $userId, float $creditAmount = 0, float $debitAmount = 0): array
{
    $currentNetWorth = $this->getCurrentNetWorth($userId);
    $currentBalance = $this->getCurrentBalance($userId); // ✅ Fetch current balance
    
    return [
        'current_net_worth' => $currentNetWorth,
        'current_balance' => $currentBalance, // ✅ Track it
        'net_worth' => $this->calculateNewNetWorth($currentNetWorth, $creditAmount),
        'balance' => $this->calculateBalance($currentBalance, $creditAmount, $debitAmount), // ✅ Add to it
    ];
}
```

### 4. Added Hidden Field in Form
The form now tracks `current_balance` in a hidden field to maintain state during form interactions.

## Test Coverage

Added comprehensive tests to prevent regression:

```
✅ it calculates new net worth correctly
✅ it calculates balance correctly (2000 + 3000 = 5000)
✅ it calculates balance with debit correctly (5000 - 1000 = 4000)
✅ it returns zero net worth when user has no savings
✅ it returns latest net worth for user
✅ it returns latest balance for user
✅ it returns correct form defaults
✅ it returns correct reset values

Tests: 8 passed (16 assertions)
```

## Verification Example

### Scenario:
1. User has existing balance: **2000 Kes**
2. User deposits: **3000 Kes**

### What Happens Now:
```php
$calculator = app(SavingsCalculator::class);
$defaults = $calculator->getFormDefaults($userId, 3000);

// Results:
$defaults['current_balance'] = 2000;  // Fetched from DB
$defaults['balance'] = 5000;          // 2000 + 3000 ✅
$defaults['net_worth'] = 5000;        // Also updated correctly
```

## Files Modified

1. **`app/Services/SavingsCalculator.php`**
   - Added `getCurrentBalance()` method
   - Fixed `calculateBalance()` method signature and logic
   - Updated `getFormDefaults()` to fetch and use current balance
   - Updated `getResetValues()` to include current_balance

2. **`app/Models/Saving.php`**
   - Added `current_balance` hidden field (already present in your version)

3. **`tests/Unit/SavingsCalculatorTest.php`**
   - Updated existing tests
   - Added new test for `getCurrentBalance()`
   - Added test for balance calculation with debits

## Impact

✅ **Balance now accumulates correctly** - deposits add to existing balance
✅ **Withdrawals supported** - debit amounts subtract from balance
✅ **Fully tested** - 16 assertions covering all scenarios
✅ **No breaking changes** - form still works the same way for users
✅ **Maintains data integrity** - previous balance is never lost

The bug is now fixed and the system correctly accumulates savings! 🎉

