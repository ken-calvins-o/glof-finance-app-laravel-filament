<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableEffect extends Model
{
    protected $fillable = [
        'receivable_id',
        'user_id',
        'account_id',
        'account_collection_id',
        'account_collection_prev_amount',
        'account_collection_post_amount',
        'saving_ids',
        'saving_snapshots',
        'deletion_reversal_saving_id',
        'reversal_saving_ids',
        'debt_id',
        'debt_prev_outstanding',
        'debt_created_by_receivable',
        'reverted',
        'reverted_at',
        'reverted_by',
    ];

    protected $casts = [
        'saving_ids' => 'array',
        'saving_snapshots' => 'array',
        'reversal_saving_ids' => 'array',
        'debt_created_by_receivable' => 'boolean',
        'reverted' => 'boolean',
        'account_collection_post_amount' => 'decimal:2',
    ];

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function accountCollection(): BelongsTo
    {
        return $this->belongsTo(AccountCollection::class);
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
