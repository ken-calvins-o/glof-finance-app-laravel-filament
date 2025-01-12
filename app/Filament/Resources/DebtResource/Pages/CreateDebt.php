<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use PhpParser\Node\Expr\Array_;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;


}
