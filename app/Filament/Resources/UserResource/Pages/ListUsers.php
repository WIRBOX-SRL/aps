<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableActions(): array
    {
        $actions = [];

        // Adaugă acțiunea de impersonare doar pentru Super Admin și Admin
        if (Auth::user()->hasRole(['Super Admin', 'Admin'])) {
            $actions[] = Impersonate::make()
                ->record(fn ($record) => $record)
                ->visible(function ($record) {
                    // Super Admin poate impersona pe toți
                    if (Auth::user()->hasRole('Super Admin')) {
                        return true;
                    }

                    // Admin poate impersona doar utilizatorii pe care i-a creat
                    if (Auth::user()->hasRole('Admin')) {
                        return $record->created_by === Auth::id();
                    }

                    return false;
                });
        }

        return $actions;
    }
}
