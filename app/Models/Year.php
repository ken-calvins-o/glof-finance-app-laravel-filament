<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Year extends Model
{
    protected $casts = [
        'year' => 'integer',
    ];

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_year')
            ->using(AccountYear::class);
    }

    public function receivables(): BelongsToMany
    {
        return $this->belongsToMany(Receivable::class, 'receivable_year')
            ->using(ReceivableYear::class);
    }

    public function payables(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'payable_year')
            ->using(PayableYear::class);
    }
}
