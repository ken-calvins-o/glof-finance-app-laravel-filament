<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MonthlyReceivable extends Pivot
{
    protected $fillable = [
        'month_id',
        'receivable_id',
    ];

    protected $table = 'monthly_receivable';

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }
}
