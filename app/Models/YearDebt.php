<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class YearDebt extends Pivot
{
    protected $fillable = [
        'year_id',
        'month_id'
    ];

    protected $table = 'year_debt';

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }
}
