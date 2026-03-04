<?php

namespace App\Services;

use App\Enums\DebtStatusEnum;
use App\Models\AccountCollection;
use App\Models\Debt;
use App\Models\MonthlyPayable;
use App\Models\Payable;
use App\Models\PayableYear;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayableCreationService
{
    /**
     * Create payable(s) and apply side-effects (account collection, savings, debt) atomically.
     *
     * Contract:
     * - Input is Filament form data for Payable create page.
     * - Creates one Payable per resolved user.
     * - Creates month/year pivot records.
     * - Applies savings/account/d debt side-effects.
     * - Returns the created payables.
     *
     * @param array $data
     * @return Collection<int, Payable>
     * @throws \Throwable
     */
    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $users = $this->determineUsers($data);

            $payables = $this->createPayables($data, $users);

            $this->createMonthlyPayables($payables, (int) $data['month_id']);
            $this->createPayableYears($payables, (int) $data['year_id']);

            $this->applySideEffects((int) $data['account_id'], $users, $payables, (bool) $data['is_general']);

            return $payables;
        });
    }

    private function determineUsers(array $data): Collection
    {
        if (!empty($data['is_general'])) {
            $excludedUserIds = $data['user_id'] ?? [];
            return User::whereNotIn('id', $excludedUserIds)->select(['id'])->get();
        }

        return collect($data['users'] ?? []);
    }

    private function createPayables(array $data, Collection $users): Collection
    {
        $payables = collect();
        $isGeneral = (bool) ($data['is_general'] ?? false);

        foreach ($users as $user) {
            $userId = $isGeneral ? (int) $user->id : (int) $user['user_id'];

            $payables->push(Payable::create([
                'account_id' => (int) $data['account_id'],
                'user_id' => $userId,
                'total_amount' => $isGeneral ? (float) $data['total_amount'] : (float) ($user['total_amount'] ?? 0),
                'is_general' => $isGeneral,
                'from_savings' => $isGeneral ? (bool) $data['from_savings'] : (bool) ($user['from_savings'] ?? false),
            ]));
        }

        return $payables;
    }

    private function createMonthlyPayables(Collection $payables, int $monthId): void
    {
        foreach ($payables as $payable) {
            MonthlyPayable::create([
                'payable_id' => $payable->id,
                'month_id' => $monthId,
            ]);
        }
    }

    private function createPayableYears(Collection $payables, int $yearId): void
    {
        foreach ($payables as $payable) {
            PayableYear::create([
                'payable_id' => $payable->id,
                'year_id' => $yearId,
            ]);
        }
    }

    private function applySideEffects(int $accountId, Collection $users, Collection $payables, bool $isGeneral): void
    {
        foreach ($users as $user) {
            $userId = $isGeneral ? (int) $user->id : (int) $user['user_id'];

            /** @var Payable|null $payable */
            $payable = $payables->firstWhere('user_id', $userId);
            if (!$payable) {
                continue;
            }

            $totalAmount = (float) $payable->total_amount;

            if ((bool) $payable->from_savings) {
                $this->deductFromSavingsBalance($userId, $totalAmount);
                continue;
            }

            $this->deductFromAccountCollectionAndRecordDebt($accountId, $userId, $totalAmount);
        }
    }

    private function deductFromSavingsBalance(int $userId, float $totalAmount): void
    {
        // Lock the latest saving row for correctness under concurrency.
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->lockForUpdate()->first();

        $currentBalance = $latestSaving ? (float) $latestSaving->balance : 0.0;
        $currentNetWorth = $latestSaving ? (float) $latestSaving->net_worth : 0.0;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0,
            'debit_amount' => $totalAmount,
            'balance' => $currentBalance - $totalAmount,
            'net_worth' => $currentNetWorth,
        ]);
    }

    private function deductFromAccountCollectionAndRecordDebt(int $accountId, int $userId, float $totalAmount): void
    {
        $accountCollection = AccountCollection::where('user_id', $userId)
            ->where('account_id', $accountId)
            ->lockForUpdate()
            ->first();

        if (!$accountCollection) {
            $accountCollection = new AccountCollection([
                'user_id' => $userId,
                'account_id' => $accountId,
                'amount' => 0,
            ]);
        }

        // Reset currentAmount for calc if already negative
        $currentAmount = max(0.0, (float) ($accountCollection->amount ?? 0));

        // Preserve existing behavior: deduct the FULL payable from the account collection,
        // and record debt as the principal shortfall only.
        [$deduction, $outstandingBalance] = $this->calculateDeductionAndOutstandingBalance($totalAmount, $currentAmount);

        if ($outstandingBalance > 0) {
            $this->updateDebtRecord($accountId, $userId, $outstandingBalance);
        }

        $accountCollection->amount = (float) ($accountCollection->amount ?? 0) - $deduction;
        $accountCollection->save();

        $this->recordNetWorthDecrease($userId, $deduction);
    }

    /**
     * @return array{0: float, 1: float} [deduction, outstandingBalance]
     */
    private function calculateDeductionAndOutstandingBalance(float $totalAmount, float $currentAmount): array
    {
        $resetCurrentAmount = max(0.0, $currentAmount);

        $deduction = $totalAmount;

        $outstandingBalance = $totalAmount > $resetCurrentAmount
            ? $totalAmount - $resetCurrentAmount
            : 0.0;

        return [$deduction, $outstandingBalance];
    }

    private function updateDebtRecord(int $accountId, int $userId, float $outstandingBalance): void
    {
        $debt = Debt::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (!$debt) {
            $debt = new Debt([
                'account_id' => $accountId,
                'user_id' => $userId,
                'outstanding_balance' => 0,
            ]);
        }

        $debt->outstanding_balance = (float) ($debt->outstanding_balance ?? 0) + $outstandingBalance;
        $debt->debt_status = DebtStatusEnum::Pending;
        $debt->save();
    }

    private function recordNetWorthDecrease(int $userId, float $deduction): void
    {
        $latestSaving = Saving::where('user_id', $userId)->latest('id')->lockForUpdate()->first();

        $currentBalance = $latestSaving ? (float) $latestSaving->balance : 0.0;
        $currentNetWorth = $latestSaving ? (float) $latestSaving->net_worth : 0.0;

        Saving::create([
            'user_id' => $userId,
            'credit_amount' => 0,
            'debit_amount' => $deduction,
            'balance' => $currentBalance,
            'net_worth' => $currentNetWorth - $deduction,
        ]);
    }
}

