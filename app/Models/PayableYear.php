<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayableYear extends Model
{
    protected $fillable = [
        'year_id',
        'payable_id'
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
