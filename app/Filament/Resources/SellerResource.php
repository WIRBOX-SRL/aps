<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Seller;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SellerResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SellerResource\RelationManagers;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class SellerResource extends Resource
{
    protected static ?string $model = Seller::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Vehicles';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'e-commerce/sellers';
    protected static ?string $navigationLabel = 'Sellers';
    protected static ?string $pluralModelLabel = 'Sellers';
    protected static ?string $modelLabel = 'Seller';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super Admin vede toți sellerii
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        // Admin vede sellerii utilizatorilor săi și cei creați direct de el
        if (Auth::user()->hasRole('Admin')) {
            return $query->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('created_by', Auth::id()); // userii creați de acest admin
                })
                ->orWhere('user_id', Auth::id()); // sellerii creați direct de admin
            });
        }

        // User vede doar sellerii săi
        if (Auth::user()->hasRole('User')) {
            return $query->where('user_id', Auth::id());
        }

        return $query->where('id', 0);
    }

    public static function canViewAny(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Seller', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Seller', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = Seller::where('user_id', Auth::id())->count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Seller', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Seller', 'edit')) {
            return false;
        }

        // Super Admin poate edita orice seller
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate edita sellerii utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user->created_by === Auth::id();
        }

        // User poate edita doar sellerii săi
        return $record->user_id === Auth::id();
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Seller', 'delete')) {
            return false;
        }

        // Super Admin poate șterge orice seller
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate șterge sellerii utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user->created_by === Auth::id();
        }

        // User poate șterge doar sellerii săi
        return $record->user_id === Auth::id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->required()
                    ->dehydrated(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                Forms\Components\Section::make('Seller Information')
                    ->schema([

                Forms\Components\Toggle::make('is_company')
                    ->label('Is Company')
                    ->default(fn () => false)
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('name')
                    ->label('Name'),
                Forms\Components\TextInput::make('email')
                    ->label('Email'),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone'),
                     Forms\Components\TagsInput::make('language')->label('Seller Languages')
                    ->placeholder('Add languages')->separator(',')->reorderable()->suggestions([
                        'English',
                        'Spanish',
                        'French',
                        'Italian',
                        'Polish',
                        'Dutch',
                        'Swedish',
                        'French',
                        'Czech',
                        'Turkish',
                        'Arabic',
                        'Hindi',
                        'Bengali',
                        'Urdu',
                        'Korean',
                        'Ukrainian',
                        'German',
                        'Chinese',
                        'Japanese',
                        'Russian',
                        'Portuguese',
                    ]),
                    Forms\Components\DatePicker::make('member_since')
                    ->label('Member Since')
                    ->default(fn () => Carbon::now()->format('d-m-Y')) // Format intern corect pentru DatePicker
                    ->displayFormat('d-m-Y')
                    ->reactive(),

                    Forms\Components\Placeholder::make('member_since_human')
                    ->label('Member Since (relativ)')
                    ->content(function (Get $get) {
                        $memberSince = $get('member_since');
                        return $memberSince ? Carbon::parse($memberSince)->diffForHumans() : 'Not set';
                    })
                    ->reactive(),

                    ])
                ->columns(3),
                   Forms\Components\Section::make('Avatar Seller')
                    ->schema([
                Forms\Components\FileUpload::make('avatar')
                    ->label('')
                    ->image()
                    ->disk(fn () => auth()->user()->getAdminForUpload()->hasCloudinaryConfigured() ? 'cloudinary' : 'public')
                    ->directory('sellers/avatars')
                    ->preserveFilenames()
                    ->visibility('public')
                    ->maxSize(1024) // 1MB
                    ->acceptedFileTypes(['image/*'])
                    ->afterStateHydrated(function ($component) {
                        if (!auth()->user()->getAdminForUpload()->hasCloudinaryConfigured()) {
                            Notification::make()
                                ->title('Cloudinary is not configured')
                                ->body('The admin does not have Cloudinary settings filled in. The images will be saved locally.')
                                ->warning()
                                ->send();
                        }
                    }),
                    ])->visible(fn (Get $get) => $get('is_company') === false),


                     Forms\Components\Section::make('Information Company')
                    ->schema([
                            Forms\Components\TextInput::make('company')
                    ->label('Company Name'),
                    Forms\Components\TextInput::make('website')
                    ->label('Website'),
                Forms\Components\Textarea::make('address')
                    ->label('Address'),

                    ])
                    ->visible(fn (Get $get) => $get('is_company') === true)
                    ->columns(3),

                         Forms\Components\Section::make('Logo Company')
                    ->schema([
                      Forms\Components\FileUpload::make('logo')
                    ->label('')
                    ->image()
                    ->disk(fn () => auth()->user()->getAdminForUpload()->hasCloudinaryConfigured() ? 'cloudinary' : 'public')
                    ->directory('sellers/logos')
                    ->preserveFilenames()
                    ->visibility('public')
                    ->maxSize(1024) // 1MB
                    ->acceptedFileTypes(['image/*']),
  ])               ->visible(fn (Get $get) => $get('is_company') === true),

                    ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([


                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('logo')
                    ->disk('public')
                    ->label('Logo')
                    ->size(50)
                    ->circular(),
                    Tables\Columns\ImageColumn::make('avatar')
                    ->disk('public')
                    ->label('Avatar')
                    ->size(50)
                    ->circular(),
                Tables\Columns\TextColumn::make('website')
                    ->sortable()
                    ->searchable()
                    ->label('Website'),
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
            'index' => Pages\ListSellers::route('/'),
            'create' => Pages\CreateSeller::route('/create'),
            'edit' => Pages\EditSeller::route('/{record}/edit'),
        ];
    }
}
