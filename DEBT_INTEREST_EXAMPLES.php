<?php

/**
 * Example: Using the Debt Interest Service
 *
 * This file demonstrates various ways to use the DebtInterestService
 * in your application.
 */

// ============================================================================
// Example 1: Basic Usage - Apply Default 1% Interest
// ============================================================================

use App\Services\DebtInterestService;

$service = new DebtInterestService();
$stats = $service->applyMonthlyInterest();

echo sprintf(
    "Processed: %d debts | Errors: %d | Total Interest: Kes %.2f",
    $stats['processed'],
    $stats['errors'],
    $stats['total_interest']
);


// ============================================================================
// Example 2: Custom Interest Rate
// ============================================================================

// Use 2% monthly interest instead of default 1%
$service = new DebtInterestService(0.02);
$stats = $service->applyMonthlyInterest();


// ============================================================================
// Example 3: Set Interest Rate After Instantiation
// ============================================================================

$service = new DebtInterestService();
$service->setInterestRate(0.015); // 1.5% interest
$currentRate = $service->getInterestRate(); // 0.015
$stats = $service->applyMonthlyInterest();


// ============================================================================
// Example 4: Using in a Console Command
// ============================================================================

namespace App\Console\Commands;

use App\Services\DebtInterestService;
use Illuminate\Console\Command;

class CustomInterestCommand extends Command
{
    protected $signature = 'app:apply-interest {--rate=0.01 : Interest rate (0.01 = 1%)}';
    protected $description = 'Apply custom interest rate to debts';

    public function handle(DebtInterestService $service): int
    {
        $rate = (float)$this->option('rate');

        if ($rate < 0 || $rate > 1) {
            $this->error('Interest rate must be between 0 and 1');
            return self::FAILURE;
        }

        $service->setInterestRate($rate);
        $this->info("Applying {$rate * 100}% interest...");

        $stats = $service->applyMonthlyInterest();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Debts Processed', $stats['processed']],
                ['Errors', $stats['errors']],
                ['Total Interest', 'Kes ' . number_format($stats['total_interest'], 2)],
            ]
        );

        return self::SUCCESS;
    }
}

// Usage:
// php artisan app:apply-interest              (default 1%)
// php artisan app:apply-interest --rate=0.02  (2%)


// ============================================================================
// Example 5: Using Dependency Injection in Controller
// ============================================================================

namespace App\Http\Controllers;

use App\Services\DebtInterestService;
use Illuminate\Http\Response;

class DebtController extends Controller
{
    public function __construct(private DebtInterestService $interestService)
    {
    }

    public function applyInterest(): Response
    {
        try {
            $stats = $this->interestService->applyMonthlyInterest();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Interest applied successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply interest: ' . $e->getMessage(),
            ], 500);
        }
    }
}


// ============================================================================
// Example 6: Using with Queue/Jobs for Large Datasets
// ============================================================================

namespace App\Jobs;

use App\Services\DebtInterestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ApplyMonthlyDebtInterest implements ShouldQueue
{
    use Queueable;

    public function __construct(private float $interestRate = 0.01)
    {
    }

    public function handle(DebtInterestService $service): void
    {
        $service->setInterestRate($this->interestRate);
        $stats = $service->applyMonthlyInterest();

        Log::info('Queued interest job completed', $stats);
    }
}

// Dispatch from command:
// ApplyMonthlyDebtInterest::dispatch(0.01)->delay(now()->addHours(1));


// ============================================================================
// Example 7: Using in Tests
// ============================================================================

namespace Tests\Unit\Services;

use App\Models\Debt;
use App\Models\User;
use App\Services\DebtInterestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtInterestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_interest_correctly()
    {
        $user = User::factory()->create();
        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'outstanding_balance' => 1000.00,
        ]);

        $service = new DebtInterestService();
        $stats = $service->applyMonthlyInterest();

        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(10.00, $stats['total_interest']);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'outstanding_balance' => 1010.00,
        ]);
    }

    public function test_custom_interest_rate()
    {
        $user = User::factory()->create();
        Debt::factory()->create([
            'user_id' => $user->id,
            'outstanding_balance' => 1000.00,
        ]);

        $service = new DebtInterestService(0.05); // 5%
        $stats = $service->applyMonthlyInterest();

        $this->assertEquals(50.00, $stats['total_interest']);
    }
}


// ============================================================================
// Example 8: Monitoring and Alerting
// ============================================================================

use App\Services\DebtInterestService;
use Illuminate\Support\Facades\Mail;
use App\Mail\InterestAppliedMail;

class MonitorDebtInterest
{
    public static function monitor(): void
    {
        $service = new DebtInterestService();
        $stats = $service->applyMonthlyInterest();

        // Alert if errors occurred
        if ($stats['errors'] > 0) {
            Mail::send(new InterestAppliedMail(
                "Interest Application - Errors Detected: {$stats['errors']} errors occurred",
                $stats
            ));
        }

        // Alert if total interest is unusual
        if ($stats['total_interest'] > 10000) {
            Mail::send(new InterestAppliedMail(
                "Interest Application - High Interest Applied: Kes " . number_format($stats['total_interest'], 2),
                $stats
            ));
        }

        // Log successful completion
        logger('Interest applied', $stats);
    }
}


// ============================================================================
// Example 9: Manual Batch Processing
// ============================================================================

use App\Models\Debt;
use App\Services\DebtInterestService;

class ProcessDebtBatch
{
    public static function processByAccount(int $accountId): array
    {
        $service = new DebtInterestService();

        $debts = Debt::where('account_id', $accountId)
            ->where('outstanding_balance', '>', 0)
            ->get();

        $stats = [
            'processed' => 0,
            'total_interest' => 0.0,
        ];

        foreach ($debts as $debt) {
            $interest = $debt->outstanding_balance * $service->getInterestRate();
            $debt->update([
                'outstanding_balance' => $debt->outstanding_balance + $interest,
            ]);

            $stats['processed']++;
            $stats['total_interest'] += $interest;
        }

        return $stats;
    }
}


// ============================================================================
// Example 10: Scheduled Task with Custom Configuration
// ============================================================================

// In routes/console.php:

use App\Services\DebtInterestService;
use Illuminate\Support\Facades\Schedule;

// Schedule with custom rate from config
Schedule::call(function () {
    $rate = config('debt.interest_rate', 0.01);
    $service = new DebtInterestService($rate);
    $service->applyMonthlyInterest();
})
->monthlyOn(1, '00:00')
->timezone(config('app.timezone'))
->withoutOverlapping();

// In config/debt.php:
// return [
//     'interest_rate' => env('DEBT_INTEREST_RATE', 0.01),
//     'process_at' => env('DEBT_INTEREST_PROCESS_TIME', '00:00'),
// ];


// ============================================================================
// Example 11: Logging with Context
// ============================================================================

use Illuminate\Support\Facades\Log;

class LogInterestWithContext
{
    public static function log(int $debtId, float $previousBalance, float $interest): void
    {
        $newBalance = $previousBalance + $interest;

        Log::channel('debt-interest')->info('Interest applied', [
            'debt_id' => $debtId,
            'previous_balance' => $previousBalance,
            'interest' => $interest,
            'new_balance' => $newBalance,
            'percentage_increase' => ($interest / $previousBalance) * 100,
            'timestamp' => now(),
        ]);
    }
}

// Configure in config/logging.php to create a separate log file:
// 'debt-interest' => [
//     'driver' => 'single',
//     'path' => storage_path('logs/debt-interest.log'),
// ],

