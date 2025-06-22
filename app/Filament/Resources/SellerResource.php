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

class SellerResource extends Resource
{
    protected static ?string $model = Seller::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'E-commerce';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'e-commerce/sellers';
    protected static ?string $navigationLabel = 'Sellers';
    protected static ?string $pluralModelLabel = 'Sellers';
    protected static ?string $modelLabel = 'Seller';


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
                    ->content(fn (Get $get) => optional(Carbon::parse($get('member_since')))->diffForHumans())
                    ->reactive(),

                    ])
                ->columns(3),
                   Forms\Components\Section::make('Avatar Seller')
                    ->schema([
                Forms\Components\FileUpload::make('avatar')
                    ->label('')
                    ->image()
                    ->disk('public')
                    ->directory('sellers/avatars')
                    ->preserveFilenames()
                    ->visibility('public')
                    ->maxSize(1024) // 1MB
                    ->acceptedFileTypes(['image/*']),
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
                    ->disk('public')
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
                    ->circular()
                    ->sortable()
                    ->searchable(),
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
