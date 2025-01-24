<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyPayable extends Model
{
    protected $fillable = [
        'payable_id',
        'month_id'
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }
}
