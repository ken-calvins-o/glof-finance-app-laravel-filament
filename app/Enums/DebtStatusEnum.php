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

    /**
     * Wording a member would understand on a statement.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Partially_Paid => 'Partly repaid',
            self::Cleared => 'Fully repaid',
            self::Pending => 'Owing',
            self::Defaulted => 'Overdue',
            default => $this->value,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Cleared => 'heroicon-o-check-circle',
            self::Partially_Paid => 'heroicon-o-chart-pie',
            self::Defaulted => 'heroicon-o-exclamation-triangle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Approved, self::Credited => 'heroicon-o-check-badge',
            default => 'heroicon-o-clock',
        };
    }

    /**
     * Statuses that still represent money owed to the group.
     *
     * @return array<string>
     */
    public static function outstandingValues(): array
    {
        return [
            self::Pending->value,
            self::Approved->value,
            self::Credited->value,
            self::Partially_Paid->value,
            self::Defaulted->value,
        ];
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

    public function getColor()
    {
        return match ($this) {
            self::Pending => Color::Orange,
            self::Approved => Color::Blue,
            self::Partially_Paid => Color::Indigo,
            self::Defaulted => Color::Red,
            self::Cleared => Color::Green,
            // Explicitly handle additional states to avoid unhandled-match errors
            self::Credited => Color::Blue,
            self::Rejected => Color::Red,

            // Fallback for any future enum values: use a neutral color
            default => Color::Slate,
        };

    }
}
