<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MonthlyPayable extends Pivot
{
    protected $fillable = [
        'payable_id',
        'month_id'
    ];

    protected $table = 'monthly_payable';

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }
}
