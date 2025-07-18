<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Vehicles';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $slug = 'vehicles/currencies';
    protected static ?string $navigationLabel = 'Currencies';
    protected static ?string $pluralModelLabel = 'Currencies';
    protected static ?string $modelLabel = 'Currency';
    protected static ?int $navigationSort = 5;


    public static function canViewAny(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Currency', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Currency', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = Currency::count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Currency', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Currency', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Currency', 'delete');
    }

    public static function getNavigationLabel(): string
    {
        return 'Currencies';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Currencies';
    }

    public static function getLabel(): ?string
    {
        return 'Currency';
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->minLength(3)
                            ->maxLength(3)
                            ->rules(['min:3', 'max:3']),
                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbol')
                            ->required(),
                    ])
                    ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
