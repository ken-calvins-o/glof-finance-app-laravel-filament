<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PayableYear extends Pivot
{
    protected $fillable = [
        'year_id',
        'payable_id'
    ];

    protected $table = 'payable_year';

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
