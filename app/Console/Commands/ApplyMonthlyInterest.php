<?php

namespace App\Console\Commands;

use App\Services\DebtInterestService;
use Illuminate\Console\Command;

class ApplyMonthlyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:apply-monthly-interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply 1% monthly interest to all outstanding debts (should run on the 1st of every month)';

    /**
     * The debt interest service instance.
     */
    private DebtInterestService $debtInterestService;

    /**
     * Create a new command instance.
     */
    public function __construct(DebtInterestService $debtInterestService)
    {
        parent::__construct();
        $this->debtInterestService = $debtInterestService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting monthly interest application process...');

        try {
            $stats = $this->debtInterestService->applyMonthlyInterest();

            $this->newLine();
            $this->info('✓ Monthly interest application completed successfully');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Debts Processed', $stats['processed']],
                    ['Errors Encountered', $stats['errors']],
                    ['Total Interest Applied', 'Kes ' . number_format($stats['total_interest'], 2)],
                ]
            );
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to apply monthly interest: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
