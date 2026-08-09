<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\ToggleButtons;

/**
 * A two-way choice where neither answer is the wrong one.
 *
 * Filament's `->boolean()` helper is built for a yes/no question, so it paints
 * the first option green with a tick and the second red with a cross. Several
 * questions in this app take two options but are not yes/no — "Their savings"
 * against "A fresh payment", "Everyone pays the same" against "Different amount
 * each" — and there the red cross reads as a warning about a perfectly ordinary
 * answer. A treasurer being told a fresh cash payment is ✗ in red hesitates over
 * a choice that has no wrong side.
 *
 * So the two options are given the same colour: whichever is selected shows in
 * the app's primary teal and the other stays quiet, which says "you have picked
 * this one" without saying "and the other was bad".
 */
final class Choice
{
    /**
     * @param  string  $whenTrue  Label for the option that stores true.
     * @param  string  $whenFalse  Label for the option that stores false.
     */
    public static function between(
        string $name,
        string $whenTrue,
        string $whenFalse,
        ?string $trueIcon = null,
        ?string $falseIcon = null,
    ): ToggleButtons {
        return ToggleButtons::make($name)
            ->boolean($whenTrue, $whenFalse)
            ->colors([1 => 'primary', 0 => 'primary'])
            // An empty array clears the tick and cross that boolean() sets.
            ->icons(array_filter([1 => $trueIcon, 0 => $falseIcon]))
            ->inline()
            ->grouped();
    }
}
