<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Filament\Resources\ReceivableResource\Pages\CreateReceivable;
use App\Models\Receivable;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The redesigned "Record money in" form asks for the period once and asks
 * "paid by" as a single question, then translates that into the per-row shape
 * the creation logic has always expected.
 *
 * That translation is the seam where the redesign could silently corrupt data,
 * so it is pinned down here.
 */
class RecordCollectionFormTest extends TestCase
{
    private function translate(array $data): array
    {
        $method = new ReflectionMethod(CreateReceivable::class, 'mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        return $method->invoke(new CreateReceivable(), $data);
    }

    private function paymentBatch(array $overrides = []): array
    {
        return array_merge([
            'entry_type' => Receivable::ENTRY_PAYMENT,
            'month_id' => 8,
            'year_id' => 3,
            'entries' => [
                [
                    'user_id' => 1,
                    'account_id' => 2,
                    'amount_contributed' => 2500,
                    'payment_source' => PaymentMode::Mobile_Money->value,
                ],
            ],
        ], $overrides);
    }

    public function test_the_period_chosen_once_is_applied_to_every_row(): void
    {
        $data = $this->translate($this->paymentBatch([
            'entries' => [
                ['user_id' => 1, 'account_id' => 2, 'amount_contributed' => 500, 'payment_source' => PaymentMode::Cash->value],
                ['user_id' => 4, 'account_id' => 5, 'amount_contributed' => 900, 'payment_source' => PaymentMode::Cash->value],
            ],
        ]));

        foreach ($data['Members Receivable'] as $row) {
            $this->assertSame(8, $row['month_id']);
            $this->assertSame(3, $row['year_id']);
        }
    }

    public function test_the_selected_payment_method_reaches_the_creation_logic(): void
    {
        $data = $this->translate($this->paymentBatch());

        $this->assertSame(PaymentMode::Mobile_Money->value, $data['Members Receivable'][0]['payment_mode']);
        $this->assertFalse($data['Members Receivable'][0]['from_savings']);
    }

    public function test_choosing_savings_as_the_source_sets_the_from_savings_flag(): void
    {
        $data = $this->translate($this->paymentBatch([
            'entries' => [[
                'user_id' => 1,
                'account_id' => 2,
                'amount_contributed' => 2500,
                'payment_source' => PaymentMode::From_Savings->value,
            ]],
        ]));

        $this->assertTrue($data['Members Receivable'][0]['from_savings']);
    }

    public function test_a_payment_is_always_stored_as_a_positive_amount(): void
    {
        $data = $this->translate($this->paymentBatch([
            'entries' => [[
                'user_id' => 1,
                'account_id' => 2,
                // Even if a stray minus sign gets through, a batch marked as a
                // payment must never turn into a debt.
                'amount_contributed' => -2500,
                'payment_source' => PaymentMode::Cash->value,
            ]],
        ]));

        $this->assertSame(2500.0, $data['Members Receivable'][0]['amount_contributed']);
    }

    public function test_an_arrears_batch_is_stored_as_a_negative_amount(): void
    {
        $data = $this->translate($this->paymentBatch([
            'entry_type' => Receivable::ENTRY_ARREARS,
            'entries' => [[
                'user_id' => 1,
                'account_id' => 2,
                'amount_contributed' => 2500,
            ]],
        ]));

        // A negative contribution is what the creation logic already treats as
        // "this member owes us", so arrears keep working exactly as before —
        // they are just no longer reached by typing a minus sign.
        $this->assertSame(-2500.0, $data['Members Receivable'][0]['amount_contributed']);
        $this->assertFalse($data['Members Receivable'][0]['from_savings']);
    }

    public function test_the_redesigned_form_keys_are_not_passed_through(): void
    {
        $data = $this->translate($this->paymentBatch());

        $this->assertArrayNotHasKey('entries', $data);
        $this->assertArrayNotHasKey('entry_type', $data);
        $this->assertArrayNotHasKey('month_id', $data);
        $this->assertArrayNotHasKey('year_id', $data);
    }
}
