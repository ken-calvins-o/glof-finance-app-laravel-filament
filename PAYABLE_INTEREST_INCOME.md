# Payable Interest Income Recording

## Overview

When creating payable records, users who do not have sufficient funds in their account collections will incur debt. The system now automatically records the interest charged on this debt as an income record, providing proper financial tracking and reporting.

## Implementation Details

### Feature Description

When a payable is processed with `from_savings = false`:

1. **Shortfall Calculation**: If the user's account collection amount is less than the payable total amount, a shortfall is calculated
2. **Interest Calculation**: Interest is calculated at 1% of the shortfall amount
3. **Debt Creation**: The outstanding balance (shortfall + interest) is added to the user's debt
4. **Income Recording**: The interest amount is automatically saved as an Income record

### Key Components

#### Modified File: `CreatePayable.php`

**New Method: `recordInterestAsIncome()`**
```php
protected function recordInterestAsIncome(int $userId, int $accountId, float $interestAmount): void
```

This method creates an Income record with:
- `user_id`: The user who incurred the debt
- `account_id`: The account where the payable was processed
- `origin`: Set to "Payable Interest" for tracking
- `interest_amount`: The calculated interest (1% of shortfall)
- `income_amount`: Set to 0 (as this is interest-based income)

**Updated Method: `calculateDeductionAndInterest()`**
- Now returns a third value: `interestAmount`
- Uses `round($shortfall * 0.01, 2)` for precise decimal calculations
- Returns `[deduction, outstandingBalance, interestAmount]`

**Updated Method: `handleDebts()`**
- Captures the `interestAmount` from calculation
- Calls `recordInterestAsIncome()` when interest is charged
- Only creates income record if `interestAmount > 0`

### Income Model Enhancement

Added `$fillable` property for explicit mass assignment protection:
```php
protected $fillable = [
    'user_id',
    'account_id',
    'source',
    'origin',
    'income_amount',
    'interest_amount',
    'description',
];
```

## Usage Example

### Scenario 1: User Incurs Debt

**Given:**
- Payable Amount: Kes 1,000
- User's Account Collection: Kes 500
- Shortfall: Kes 500

**Calculation:**
```
Shortfall = 1000 - 500 = 500
Interest = 500 × 0.01 = 5.00
Deduction = 1000 + 5 = 1005
Outstanding Balance = 1005 - 500 = 505
```

**Result:**
1. Debt record created with `outstanding_balance = 505.00`
2. Income record created:
   ```php
   [
       'user_id' => 123,
       'account_id' => 456,
       'origin' => 'Payable Interest',
       'interest_amount' => 5.00,
       'income_amount' => 0
   ]
   ```

### Scenario 2: User Has Sufficient Funds

**Given:**
- Payable Amount: Kes 1,000
- User's Account Collection: Kes 2,000

**Result:**
- No debt created
- No income record created
- Account collection reduced by Kes 1,000

## Database Schema

### Income Table Fields Used
```sql
- user_id (foreignId)
- account_id (foreignId, nullable)
- origin (string, nullable)
- interest_amount (decimal:10,2, default 0.00)
- income_amount (string, nullable)
```

## Testing

Comprehensive tests have been created in `tests/Feature/PayableInterestIncomeTest.php`:

### Test Cases

1. **test_interest_is_recorded_as_income_when_debt_is_incurred**
   - Verifies Income record creation with correct values
   - Confirms debt is created with proper outstanding balance

2. **test_no_income_record_when_no_debt_incurred**
   - Ensures no Income record when user has sufficient funds
   - Confirms no unnecessary records are created

3. **test_interest_calculation_with_various_amounts**
   - Tests multiple scenarios with different shortfall amounts
   - Validates interest calculation accuracy across edge cases

### Running Tests
```bash
php artisan test --filter=PayableInterestIncomeTest
```

## Benefits

### 1. **Complete Financial Tracking**
   - All interest charges are now properly recorded
   - Clear audit trail of income from debt interest

### 2. **Reporting Accuracy**
   - Income reports include interest from payables
   - Can filter by origin "Payable Interest" for specific analysis

### 3. **Modular Design**
   - Separated concerns with dedicated method
   - Easy to modify interest rate or calculation logic
   - Follows single responsibility principle

### 4. **Performance Optimized**
   - Only creates records when necessary (interest > 0)
   - Single database insert per interest charge
   - Efficient calculation with early returns

### 5. **Maintainability**
   - Well-documented code with PHPDoc comments
   - Consistent with existing patterns (e.g., CreateLoan.php)
   - Clear naming conventions

## Code Quality

### Best Practices Applied

✅ **Separation of Concerns**: Interest recording isolated in its own method  
✅ **Single Responsibility**: Each method has one clear purpose  
✅ **DRY Principle**: Reuses existing calculation logic  
✅ **Type Safety**: Proper type hints for all parameters  
✅ **Documentation**: Comprehensive PHPDoc comments  
✅ **Consistency**: Follows existing codebase patterns  
✅ **Testing**: Full test coverage for edge cases  
✅ **Performance**: Optimized with conditional execution  

## Future Enhancements

Potential improvements for future iterations:

1. **Configurable Interest Rate**: Move 0.01 to a config file
2. **Interest Rate History**: Track rate changes over time
3. **Bulk Income Reporting**: Aggregated interest reports
4. **Notifications**: Alert users when interest is charged
5. **Interest Waivers**: Admin ability to waive interest charges

## Related Files

- **Model**: `app/Models/Income.php`
- **Resource**: `app/Filament/Resources/PayableResource/Pages/CreatePayable.php`
- **Migration**: `database/migrations/2024_10_29_111316_create_incomes_table.php`
- **Tests**: `tests/Feature/PayableInterestIncomeTest.php`
- **Similar Pattern**: `app/Filament/Resources/LoanResource/Pages/CreateLoan.php`

## Support

For questions or issues related to this feature, please review:
- This documentation
- The test cases for usage examples
- The CreateLoan.php implementation for similar patterns

