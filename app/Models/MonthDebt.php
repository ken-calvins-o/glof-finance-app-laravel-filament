<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MonthDebt extends Pivot
{
    protected $fillable = [
        'debt_id',
        'month_id'
    ];

    protected $table = 'month_debt';

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

}
