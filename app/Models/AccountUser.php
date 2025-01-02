<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AccountUser extends Pivot
{
    protected $fillable = [
        'account_id',
        'user_id',
        'receivable_id',
        'amount_due',
        'outstanding_balance',
        'status',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'status' => PaymentStatusEnum::class, // Casts to the PaymentStatusEnum
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
