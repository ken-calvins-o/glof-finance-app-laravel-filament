<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Members see their own money; treasurers see everyone's.
 *
 * The app already had a role on every user but never used it, so any member who
 * could sign in saw the full group's savings, debts and loans, and could edit
 * them. For a savings group that is both a privacy problem and a usability one
 * — a member opening "Collections" was met with 250 rows, only eight of which
 * were theirs.
 *
 * Applied to any resource whose model has a `user_id`.
 *
 * Note on the shape of this trait: the scoping deliberately lives in
 * getEloquentQuery() and nowhere else, and resources that need to add their own
 * eager loading override baseEloquentQuery() instead. A trait method loses to a
 * method defined on the class itself, so if a resource declared its own
 * getEloquentQuery() it would silently drop the filter and start leaking every
 * member's records — which is exactly what happened the first time this was
 * wired up. Splitting the two makes that mistake impossible to make by accident.
 */
trait RestrictsToOwnRecords
{
    final public static function getEloquentQuery(): Builder
    {
        $query = static::baseEloquentQuery();

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->where(static::getOwnerColumn(), $user->id);
        }

        return $query;
    }

    /**
     * Override this — not getEloquentQuery() — to add eager loading or other
     * query customisation to a restricted resource.
     */
    protected static function baseEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    protected static function getOwnerColumn(): string
    {
        return 'user_id';
    }

    /**
     * Only treasurers record money movements. A member viewing their own
     * statement should never be handed a "Create" button they cannot use
     * correctly.
     */
    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canEdit(mixed $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canDelete(mixed $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
