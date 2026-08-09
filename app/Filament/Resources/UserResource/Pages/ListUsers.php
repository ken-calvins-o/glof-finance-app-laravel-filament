<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\MemberStatus;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Everyone in the group. Open a member to see their full statement.';
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('member_status', MemberStatus::Active->value)),

            'inactive' => Tab::make('Inactive')
                ->icon('heroicon-o-pause-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('member_status', MemberStatus::Inactive->value)),

            'suspended' => Tab::make('Suspended')
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('member_status', MemberStatus::Suspended->value)),

            'all' => Tab::make('Everyone'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'active';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add a member')
                ->icon('heroicon-o-plus'),
        ];
    }
}
