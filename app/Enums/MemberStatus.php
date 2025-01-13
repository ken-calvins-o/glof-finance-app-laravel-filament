<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum MemberStatus: string
{
    case Active = "Active";
    case Inactive = "Inactive";
    case Suspended = "Suspended";

    public function getColor()
    {
        return match ($this) {
            self::Inactive => Color::Orange,
            self::Suspended => Color::Red,
            self::Active => Color::Green,
        };

    }
}
