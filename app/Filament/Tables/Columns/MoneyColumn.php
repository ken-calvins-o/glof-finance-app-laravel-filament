<?php

namespace App\Filament\Tables\Columns;

use App\Support\Money;
use Filament\Tables\Columns\TextColumn;

/**
 * One money column, used everywhere.
 *
 * Before this, amounts were formatted three different ways across the app —
 * `->prefix('Kes ')` on one table, `'Kes ' . number_format()` on another, some
 * left-aligned, some marked `->numeric()` and some not. The result was that
 * the same figure looked different depending on which screen you were on, and
 * columns of amounts did not line up for comparison.
 *
 * Rules encoded here:
 *  - always "KES 1,234.00", never "Kes" or a bare number;
 *  - always right-aligned, because that is how you scan a column of money;
 *  - negatives shown in danger colour, since in this app a negative amount
 *    always means the member owes rather than owns.
 */
class MoneyColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? str($name)->headline())
            ->alignEnd()
            ->sortable()
            ->formatStateUsing(fn ($state) => Money::kes($state))
            ->color(fn ($state) => (float) $state < 0 ? 'danger' : null)
            ->weight('medium');
    }

    /**
     * A money column that also totals itself in the table footer — used where
     * "what does this list add up to?" is the actual question being asked.
     */
    public static function withTotal(string $name, ?string $label = null): TextColumn
    {
        return static::make($name, $label)
            ->summarize(
                \Filament\Tables\Columns\Summarizers\Sum::make()
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => Money::kes($state))
            );
    }
}
