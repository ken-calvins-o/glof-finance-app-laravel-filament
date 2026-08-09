<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum RoleEnum: string
{
    case Administrator = 'Administrator';
    case Member = 'Member';

    /**
     * What the role means in the group, rather than what it is called in code.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Administrator => 'Treasurer / Admin',
            self::Member => 'Member',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Administrator => 'Can record money in and out, issue loans and manage members.',
            self::Member => 'Can only view their own savings, contributions and loans.',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Administrator => Color::Amber,
            self::Member => Color::Slate,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Administrator => 'heroicon-o-key',
            self::Member => 'heroicon-o-user',
        };
    }

    /**
     * Options keyed by value, for use in selects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
