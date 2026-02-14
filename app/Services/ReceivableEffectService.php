<?php

namespace App\Services;

use App\Enums\DebtStatusEnum;
use App\Models\Receivable;
use App\Models\ReceivableEffect;
use App\Models\AccountCollection;
use App\Models\Saving;
use App\Models\Debt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReceivableEffectService
{
    /**
     * Record the effects that were caused by creating this receivable.
     * This should be called after the Receivable has been created and related
     * AccountCollection / Saving / Debt changes have been persisted.
     */
    public function recordCreationEffects(Receivable $receivable): ReceivableEffect
    {
        // Capture current AccountCollection for this user/account
        $accountCollection = AccountCollection::where('user_id', $receivable->user_id)
            ->where('account_id', $receivable->account_id)
            ->first();

        $accountPrevAmount = $accountCollection?->amount ?? null;

        // Collect savings entries that belong to the user and are recent.
        // We look for savings that reference the user's id and have the same amount/net_worth changes
        // It's not perfect but will capture the entries created immediately after receivable creation.
        $savings = Saving::where('user_id', $receivable->user_id)
            ->latest('id')
            ->limit(10)
            ->get();

        $savingIds = $savings->pluck('id')->toArray();

        // Capture debt info if exists
        $debt = Debt::where('user_id', $receivable->user_id)
            ->where('account_id', $receivable->account_id)
            ->first();

        $debtPrevOutstanding = $debt?->outstanding_balance ?? null;

        $effect = ReceivableEffect::create([
            'receivable_id' => $receivable->id,
            'account_collection_id' => $accountCollection?->id,
            'account_collection_prev_amount' => $accountPrevAmount,
            'saving_ids' => $savingIds,
            'debt_id' => $debt?->id,
            'debt_prev_outstanding' => $debtPrevOutstanding,
            'debt_created_by_receivable' => false, // creation flow sets this explicitly upstream when applicable
        ]);

        return $effect;
    }

    /**
     * Revert the effects for a receivable. This is called inside a transaction
     * and should reverse: savings, account_collections, and debt changes.
     */
    public function revertEffectsForReceivable(Receivable $receivable): void
    {
        DB::transaction(function () use ($receivable) {
            // Find the most recent effect for this receivable
            $effect = ReceivableEffect::where('receivable_id', $receivable->id)
                ->latest('id')
                ->first();

            // If no effect recorded, attempt a best-effort revert (backcompat for older records)
            if (! $effect) {
                // Best-effort: handle debt and account_collection for negative receivables
                $reversalSavingIds = [];

                // 1) Best-effort debt revert for negative receivables
                if ((float) $receivable->amount_contributed < 0) {
                    $positiveAmount = (float) round(abs($receivable->amount_contributed), 2);

                    $debt = Debt::where('user_id', $receivable->user_id)
                        ->where('account_id', $receivable->account_id)
                        ->lockForUpdate()
                        ->first();

                    if ($debt) {
                        $currentOutstanding = (float) $debt->outstanding_balance;
                        $newOutstanding = max(0, $currentOutstanding - $positiveAmount);

                        // Heuristic to decide whether to delete the debt entirely:
                        // - if the debt was likely created by this receivable (timestamps very close)
                        // - or if outstanding exactly equals the positive amount
                        $createdAtDiff = null;
                        try {
                            $createdAtDiff = $debt->created_at && $receivable->created_at
                                ? abs(strtotime($debt->created_at) - strtotime($receivable->created_at))
                                : null;
                        } catch (\Throwable $e) {
                            $createdAtDiff = null;
                        }

                        if ($currentOutstanding === $positiveAmount || ($createdAtDiff !== null && $createdAtDiff <= 5)) {
                            // delete debt (soft-delete if model supports it)
                            $debt->delete();
                        } else {
                            $debt->outstanding_balance = $newOutstanding;
                            $debt->debt_status = $newOutstanding <= 0 ? DebtStatusEnum::Cleared : $debt->debt_status;
                            $debt->save();
                        }
                    }
                }

                // 2) Best-effort AccountCollection revert: subtract the receivable's applied change
                $ac = AccountCollection::where('user_id', $receivable->user_id)
                    ->where('account_id', $receivable->account_id)
                    ->lockForUpdate()
                    ->first();

                if ($ac) {
                    // We previously added amount_contributed to this row; to revert, subtract it
                    $ac->amount = (float) ($ac->amount ?? 0) - (float) $receivable->amount_contributed;
                    $ac->save();
                }

                // Persist a ReceivableEffect row to record the best-effort revert for audit
                $ae = ReceivableEffect::create([
                    'receivable_id' => $receivable->id,
                    'account_collection_id' => $ac?->id ?? null,
                    'account_collection_prev_amount' => null, // unknown in backcompat case
                    'saving_ids' => null,
                    'debt_id' => $debt?->id ?? null,
                    'debt_prev_outstanding' => $debt?->outstanding_balance ?? null,
                    'debt_created_by_receivable' => false,
                    'reverted' => true,
                    'reverted_at' => now(),
                    'reverted_by' => auth()->id() ?? null,
                    'reversal_saving_ids' => $reversalSavingIds,
                ]);

                return;
            }

            $reversalSavingIds = [];

            // Idempotency: if effect already reverted, no-op
            if ($effect->reverted) {
                return;
            }

             // 1) Reverse savings created by the receivable by creating offsetting reversal savings.
            // Prefer precise snapshots if available; otherwise fall back to ids and compute
            if (! empty($effect->saving_snapshots) && is_array($effect->saving_snapshots)) {
                foreach ($effect->saving_snapshots as $snap) {
                    // Create a reversal saving that restores previous net_worth precisely
                    $reversal = Saving::create([
                        'user_id' => $snap['user_id'] ?? $receivable->user_id,
                        'credit_amount' => $snap['debit_amount'] ?? 0.00,
                        'debit_amount' => $snap['credit_amount'] ?? 0.00,
                        'balance' => $snap['prev_balance'] ?? ($snap['balance'] ?? 0.00),
                        'net_worth' => $snap['prev_net_worth'] ?? null,
                    ]);

                    $reversalSavingIds[] = $reversal->id;
                }
            } elseif (! empty($effect->saving_ids) && is_array($effect->saving_ids)) {
                $originalSavings = Saving::whereIn('id', $effect->saving_ids)->get();

                foreach ($originalSavings as $orig) {
                    // Best-effort reversal: swap credit/debit and try to invert net_worth change
                    $reversal = Saving::create([
                        'user_id' => $orig->user_id,
                        'credit_amount' => $orig->debit_amount ?? 0.00,
                        'debit_amount' => $orig->credit_amount ?? 0.00,
                        'balance' => $orig->balance ?? 0.00,
                        // If snapshot not available, we attempt a conservative no-op on net_worth
                        'net_worth' => $orig->net_worth ?? null,
                    ]);

                    $reversalSavingIds[] = $reversal->id;
                }
            }

            // 2) Restore the AccountCollection amount to the previous snapshot
            if ($effect->account_collection_id) {
                $ac = AccountCollection::find($effect->account_collection_id);

                if ($ac) {
                    // If we have previous value recorded, set it back.
                    if (! is_null($effect->account_collection_prev_amount)) {
                        $ac->amount = $effect->account_collection_prev_amount;
                        $ac->save();
                    } else {
                        // If previous was null then the row probably didn't exist before; delete it.
                        $ac->delete();
                    }
                }
            } else {
                // Effect didn't record an account_collection_id (row didn't exist at creation).
                // Best-effort: find the AccountCollection row for this user/account and revert the applied change.
                $ac = AccountCollection::where('user_id', $receivable->user_id)
                    ->where('account_id', $receivable->account_id)
                    ->lockForUpdate()
                    ->first();

                if ($ac) {
                    if (! is_null($effect->account_collection_prev_amount)) {
                        // If prev amount is recorded, restore to it
                        $ac->amount = $effect->account_collection_prev_amount;
                        $ac->save();
                    } else {
                        // No prev snapshot: subtract the receivable's applied change
                        // Note: when a receivable with negative amount was applied, updateOrCreate did: amount = prev + amount_contributed
                        // To revert, set amount = current - amount_contributed
                        $ac->amount = (float) ($ac->amount ?? 0) - (float) $receivable->amount_contributed;

                        // If this yields effectively zero and there was no previous row, remove it to reflect original non-existence
                        if (abs((float)$ac->amount) < 0.0001) {
                            $ac->delete();
                        } else {
                            $ac->save();
                        }
                    }
                }
            }

            // 3) If a debt was created by this receivable, reverse it.
            if ($effect->debt_id) {
                $debt = Debt::find($effect->debt_id);

                if ($debt) {
                    // If we recorded a previous outstanding balance, restore it (covers updated-existing-debt case)
                    if (! is_null($effect->debt_prev_outstanding)) {
                        $debt->outstanding_balance = $effect->debt_prev_outstanding;
                        $debt->debt_status = $effect->debt_prev_outstanding <= 0 ? DebtStatusEnum::Cleared : $debt->debt_status;
                        $debt->save();
                    } elseif ($effect->debt_created_by_receivable) {
                        // The debt was created by the receivable and we have no previous outstanding: delete it.
                        $debt->delete();
                    } else {
                        // Fallback heuristic: try to reduce outstanding by the receivable amount if timestamps/amounts align
                        $receivablePositive = (float) round(abs($receivable->amount_contributed), 2);
                        $shouldAdjust = false;

                        try {
                            if ($debt->created_at && $receivable->created_at) {
                                $diff = abs(strtotime($debt->created_at) - strtotime($receivable->created_at));
                                if ($diff <= 10) {
                                    $shouldAdjust = true;
                                }
                            }
                        } catch (\Throwable $e) {
                            $shouldAdjust = true;
                        }

                        if ($shouldAdjust && round((float)$debt->outstanding_balance, 2) >= $receivablePositive) {
                            $debt->outstanding_balance = max(0, (float)$debt->outstanding_balance - $receivablePositive);
                            $debt->debt_status = $debt->outstanding_balance <= 0 ? DebtStatusEnum::Cleared : $debt->debt_status;
                            $debt->save();
                        }
                    }
                }
            }

            // Mark effect as reverted and store reversal saving ids for audit
            $effect->reverted = true;
            $effect->reverted_at = now();
            $effect->reverted_by = auth()->id() ?? null;
            $effect->reversal_saving_ids = $reversalSavingIds;
            $effect->save();
        });
    }

    /**
     * Restore effects when a receivable is restored.
     * This should re-apply what was reverted on delete where possible.
     */
    public function restoreEffectsForReceivable(Receivable $receivable): void
    {
        DB::transaction(function () use ($receivable) {
            $effect = ReceivableEffect::where('receivable_id', $receivable->id)
                ->latest('id')
                ->first();

            if (! $effect) {
                return;
            }

            // 1) Recreate AccountCollection row if it was deleted during revert
            if ($effect->account_collection_id) {
                $ac = AccountCollection::withTrashed()->find($effect->account_collection_id);

                if ($ac) {
                    // Restore row if soft-deleted
                    if (method_exists($ac, 'restore') && $ac->trashed()) {
                        $ac->restore();
                    }

                    // Restore amount to the value that was set when receivable existed (approximate)
                    // We don't have the "post" amount stored, so leave as-is.
                }
            }

            // 2) Recreate savings entries if they were deleted. We cannot reconstruct exact values
            // reliably; therefore we do not auto-create savings on restore. Instead, log and skip.

            // 3) If debt was previously deleted because it was created by this receivable, try to restore it
            if ($effect->debt_id && $effect->debt_created_by_receivable) {
                $debt = Debt::withTrashed()->find($effect->debt_id);

                if ($debt && method_exists($debt, 'restore') && $debt->trashed()) {
                    $debt->restore();
                }

                // restore outstanding balance if we have previous value
                if ($debt && ! is_null($effect->debt_prev_outstanding)) {
                    $debt->outstanding_balance = $effect->debt_prev_outstanding;
                    $debt->save();
                }
            }
        });
    }
}

