<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Filament\Resources\CountryResource\RelationManagers;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Locations';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $slug = 'locations/countries';
    protected static ?string $navigationLabel = 'Countries';
    protected static ?string $pluralModelLabel = 'Countries';
    protected static ?string $modelLabel = 'Country';

    public static function canViewAny(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Country', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Country', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = Country::count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Country', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Country', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Country', 'delete');
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
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable(),

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
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
