<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AccountCollection extends Pivot
{
    protected $fillable = [
        'account_id',
        'user_id',
        'receivable_id',
        'amount',
    ];

    protected $table = 'account_collections';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

}
