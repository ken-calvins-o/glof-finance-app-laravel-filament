<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Month extends Model
{
    protected $casts = [
        'name' => 'string',
    ];

    public function receivables(): BelongsToMany
    {
        return $this->belongsToMany(Receivable::class, 'monthly_receivable')
            ->using(MonthlyReceivable::class);
    }

    public function payables(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'monthly_payable')
            ->using(MonthlyPayable::class);
    }



}
