<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum DebtStatusEnum: string
{
    case Approved = "Approved";
    case Cleared = "Cleared";
    case Credited = "Credited";
    case Defaulted = "Defaulted";
    case Partially_Paid = "Partially Paid";

    case Pending = "Pending";
    case Rejected = "Rejected";

    public function getColor()
    {
        return match ($this) {
            self::Pending => Color::Orange,
            self::Approved => Color::Blue,
            self::Partially_Paid => Color::Indigo,
            self::Defaulted => Color::Red,
            self::Cleared => Color::Green,
        };

    }
}
