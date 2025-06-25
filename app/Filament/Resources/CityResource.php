<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Locations';
    protected static ?int $navigationSort = 6;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $slug = 'locations/cities';
    protected static ?string $navigationLabel = 'Cities';
    protected static ?string $pluralModelLabel = 'Cities';
    protected static ?string $modelLabel = 'City';
    protected static ?string $navigationLabelPlural = 'Cities';

    public static function canViewAny(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('City', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('City', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = City::count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('City', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('City', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('City', 'delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('state.name')
                    ->sortable()
                    ->searchable()
                    ->label('State'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
