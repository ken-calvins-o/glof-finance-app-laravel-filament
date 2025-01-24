<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReceivableYear extends Pivot
{
    protected $fillable = [
        'year_id',
        'receivable_id'
    ];

    protected $table = 'receivable_year';

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }
}
