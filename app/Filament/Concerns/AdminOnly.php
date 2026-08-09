<?php

namespace App\Filament\Concerns;

/**
 * Hides a resource entirely from anyone who is not a treasurer/admin.
 *
 * Used for the setup and group-wide screens (funds, group income, the member
 * register) where there is no meaningful "my own" view to fall back to.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
