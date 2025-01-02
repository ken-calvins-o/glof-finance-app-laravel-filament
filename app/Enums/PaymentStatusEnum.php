<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum PaymentStatusEnum: string
{
    case Credited = "Credited";
    case Completed = "Completed";
    case Partially_Paid = "Partially Paid";

    case Pending = "Pending";
    case Overdue = "Overdue";

    public function getColor()
    {
        return match ($this) {
            self::Pending => Color::Indigo,
            self::Credited => Color::Orange,
            self::Completed => Color::Green,
            self::Partially_Paid => Color::Yellow,
            self::Overdue => Color::Red,
        };

    }
}
