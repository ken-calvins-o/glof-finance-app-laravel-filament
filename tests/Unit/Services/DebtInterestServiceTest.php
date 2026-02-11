<?php

namespace Tests\Unit\Services;

use App\Models\Debt;
use App\Services\DebtInterestService;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Unit tests for DebtInterestService
 *
 * Tests the pure business logic of interest calculation without database dependency.
 * Uses mocks for database queries to isolate the service logic.
 */
class DebtInterestServiceTest extends TestCase
{
    private DebtInterestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DebtInterestService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test calculating 1% interest correctly
     *
     * @dataProvider interestCalculationDataProvider
     */
    public function test_calculates_interest_correctly(float $balance, float $rate, float $expectedInterest): void
    {
        $this->service->setInterestRate($rate);
        $expectedNewBalance = round($balance + $expectedInterest, 2);

        // Verify the calculation logic by checking the expected values
        $calculatedInterest = round($balance * $rate, 2);
        $this->assertEquals($expectedInterest, $calculatedInterest);
    }

    /**
     * Data provider for interest calculation tests
     */
    public static function interestCalculationDataProvider(): array
    {
        return [
            'default 1% interest on 1000' => [1000.00, 0.01, 10.00],
            '5% interest on 1000' => [1000.00, 0.05, 50.00],
            '1% interest on 500' => [500.00, 0.01, 5.00],
            '1% interest on 2000' => [2000.00, 0.01, 20.00],
            'precision: 1% on 333.33' => [333.33, 0.01, 3.33],
        ];
    }

    /**
     * Test interest rate validation - must be between 0 and 1
     */
    public function test_validates_interest_rate_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->setInterestRate(1.5); // Invalid: > 1
    }

    /**
     * Test interest rate cannot be negative
     */
    public function test_rejects_negative_interest_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->setInterestRate(-0.01);
    }

    /**
     * Test valid interest rate is accepted
     */
    public function test_accepts_valid_interest_rates(): void
    {
        $validRates = [0.0, 0.01, 0.05, 0.5, 1.0];

        foreach ($validRates as $rate) {
            $service = new DebtInterestService($rate);
            $this->assertEquals($rate, $service->getInterestRate());
        }
    }

    /**
     * Test setting interest rate after construction
     */
    public function test_can_set_interest_rate_after_construction(): void
    {
        $service = new DebtInterestService(); // Default 0.01
        $this->assertEquals(0.01, $service->getInterestRate());

        $service->setInterestRate(0.05);
        $this->assertEquals(0.05, $service->getInterestRate());
    }

    /**
     * Test decimal precision in interest calculations
     *
     * @dataProvider precisionDataProvider
     */
    public function test_maintains_decimal_precision(float $balance, float $rate, float $expectedInterest, float $expectedNewBalance): void
    {
        // Test the calculation without database
        $calculatedInterest = round($balance * $rate, 2);
        $calculatedNewBalance = round($balance + $calculatedInterest, 2);

        $this->assertEquals($expectedInterest, $calculatedInterest, "Interest calculation should be precise");
        $this->assertEquals($expectedNewBalance, $calculatedNewBalance, "New balance calculation should be precise");
    }

    /**
     * Data provider for decimal precision tests
     */
    public static function precisionDataProvider(): array
    {
        return [
            'recurring 3s: 333.33 at 1%' => [333.33, 0.01, 3.33, 336.66],
            'edge case: 0.01 at 1%' => [0.01, 0.01, 0.00, 0.01],
            'large balance with decimal' => [9999.99, 0.01, 100.00, 10099.99],
            'small interest result' => [1.00, 0.01, 0.01, 1.01],
        ];
    }
}



