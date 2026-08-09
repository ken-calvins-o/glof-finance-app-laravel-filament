<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum MemberStatus: string
{
    case Active = "Active";
    case Inactive = "Inactive";
    case Suspended = "Suspended";

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Contributing normally and included in shared payments.',
            self::Inactive => 'Not currently contributing. Kept for historical records.',
            self::Suspended => 'Temporarily blocked from loans and new contributions.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::Inactive => 'heroicon-o-pause-circle',
            self::Suspended => 'heroicon-o-no-symbol',
        };
    }

    public function getColor()
    {
        return match ($this) {
            self::Inactive => Color::Orange,
            self::Suspended => Color::Red,
            self::Active => Color::Green,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
