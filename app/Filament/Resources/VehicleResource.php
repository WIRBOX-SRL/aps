<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Vehicles';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'brand';
    protected static ?string $slug = 'vehicles';
    protected static ?string $navigationLabel = 'Vehicles';
    protected static ?string $pluralModelLabel = 'Vehicles';
    protected static ?string $modelLabel = 'Vehicle';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super Admin vede toate vehiculele
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        // Admin vede vehiculele create de el și vehiculele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $query->where(function ($q) {
                $q->where('user_id', Auth::id()) // Vehiculele create de Admin
                  ->orWhereHas('user', function ($subQ) {
                      $subQ->where('created_by', Auth::id()); // Vehiculele utilizatorilor săi
                  });
            });
        }

        // User vede doar vehiculele sale
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
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Vehicle', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Vehicle', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = Vehicle::where('user_id', Auth::id())->count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Vehicle', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Vehicle', 'edit')) {
            return false;
        }

        // Super Admin poate edita orice vehicul
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate edita vehiculele create de el și vehiculele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user_id === Auth::id() || // Vehiculele create de Admin
                   ($record->user && $record->user->created_by === Auth::id()); // Vehiculele utilizatorilor săi
        }

        // User poate edita doar vehiculele sale
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
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Vehicle', 'delete')) {
            return false;
        }

        // Super Admin poate șterge orice vehicul
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate șterge vehiculele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user && (
                $record->user_id === Auth::id() ||
                $record->user->created_by === Auth::id()
            );
        }

        // User poate șterge doar vehiculele sale
        return $record->user_id === Auth::id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle Information')
                    ->schema([
                        Forms\Components\Hidden::make('title')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $brand = $get('brand');
                                $model = $get('model');
                                $year = $get('year');

                                if ($brand && $model && $year) {
                                    $set('title', "{$brand} {$model} {$year}");
                                }
                            }),
                        Forms\Components\TextInput::make('brand')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $brand = $get('brand');
                                $model = $get('model');
                                $year = $get('year');

                                if ($brand && $model && $year) {
                                    $set('title', "{$brand} {$model} {$year}");
                                }
                            }),
                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $brand = $get('brand');
                                $model = $get('model');
                                $year = $get('year');

                                if ($brand && $model && $year) {
                                    $set('title', "{$brand} {$model} {$year}");
                                }
                            }),
                        Forms\Components\TextInput::make('year')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y') + 1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $brand = $get('brand');
                                $model = $get('model');
                                $year = $get('year');

                                if ($brand && $model && $year) {
                                    $set('title', "{$brand} {$model} {$year}");
                                }
                            }),
                        // Forms\Components\TextInput::make('price')
                        //     ->numeric()
                        //     ->prefix('€')
                        //     ->required()
                        //     ->helperText('Price without VAT'),
                        // Forms\Components\TextInput::make('vat')
                        //     ->numeric()
                        //     ->suffix('%')
                        //     ->default(19.00)
                        //     ->minValue(0)
                        //     ->maxValue(100)
                        //     ->required()
                        //     ->helperText('VAT percentage'),
                        Forms\Components\Select::make('category_level_1')
                            ->label('Category Level 1')
                            ->options(function () {
                                return Category::whereNull('root_id')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Select main category')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset lower levels when upper level changes
                                $set('category_level_2', null);
                                $set('category_level_3', null);
                                $set('category_id', null);
                            }),

                        Forms\Components\Select::make('category_level_2')
                            ->label('Category Level 2')
                            ->options(function (callable $get) {
                                $level1Id = $get('category_level_1');
                                if (!$level1Id) {
                                    return [];
                                }
                                return Category::where('root_id', $level1Id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Select subcategory')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset lower levels when upper level changes
                                $set('category_level_3', null);
                                $set('category_id', null);
                            })
                            ->visible(fn (callable $get) => $get('category_level_1') !== null),

                        Forms\Components\Select::make('category_level_3')
                            ->label('Category Level 3')
                            ->options(function (callable $get) {
                                $level2Id = $get('category_level_2');
                                if (!$level2Id) {
                                    return [];
                                }
                                return Category::where('root_id', $level2Id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Select sub-subcategory')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('category_id', $state);
                            })
                            ->visible(fn (callable $get) => $get('category_level_2') !== null),

                        Forms\Components\Hidden::make('category_id')
                            ->afterStateHydrated(function ($state, callable $set, $get) {
                                // When editing, populate the level selects based on the saved category_id
                                if ($state) {
                                    $category = Category::find($state);
                                    if ($category) {
                                        self::populateCategoryLevels($category, $set);
                                    }
                                }
                            })
                            ->required()
                            ->helperText('The final selected category ID will be saved here'),
                        Forms\Components\RichEditor::make('description')
                        ->toolbarButtons([
                            'redo',
                            'undo',
                        ])
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Images')
                    ->description('Upload multiple images for this vehicle')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->image()
                            ->maxFiles(20)
                            ->disk(fn () => auth()->user()->getAdminForUpload()->hasCloudinaryConfigured() ? 'cloudinary' : 'public')
                            ->directory('vehicles')
                            ->visibility('public')
                            ->helperText('Upload up to 20 vehicle images')
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component) {
                                if (!auth()->user()->getAdminForUpload()->hasCloudinaryConfigured()) {
                                    Notification::make()
                                        ->title('Cloudinary is not configured')
                                        ->body('The admin does not have Cloudinary settings filled in. The images will be saved locally.')
                                        ->warning()
                                        ->send();
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Specifications')
                    ->description('Vehicle technical specifications')
                    ->schema([
                        Forms\Components\Repeater::make('specifications')
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Specification Name')
                                    ->required()
                                    ->placeholder('e.g., Engine Size, Fuel Type, Transmission'),
                                Forms\Components\TextInput::make('value')
                                    ->label('Specification Value')
                                    ->required()
                                    ->placeholder('e.g., 2.0L, Diesel, Automatic'),
                            ])
                            ->columns(2)
                            ->defaultItems(3)
                            ->default([
                                [
                                    'key' => 'Type of ads',
                                    'value' => 'For sale',
                                ],
                                [
                                    'key' => 'Status',
                                    'value' => 'Used - Condition not included',
                                ],
                                [
                                    'key' => 'Hours',
                                    'value' => '',
                                ],
                                [
                                    'key' => 'Serial Number',
                                    'value' => '',
                                ],
                                [
                                    'key' => 'First-Hand',
                                    'value' => '',
                                ],
                            ])
                            ->reorderable(false)
                            ->addActionLabel('Add Specification')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('images_count')
                    ->label('Images')
                    ->getStateUsing(fn ($record) => $record->images ? count($record->images) : 0)
                    ->sortable(),
                // Tables\Columns\TextColumn::make('price')
                //     ->money('EUR')
                //     ->sortable()
                //     ->label('Price (excl. VAT)'),
                // Tables\Columns\TextColumn::make('price_with_vat')
                //     ->money('EUR')
                //     ->label('Price (incl. VAT)')
                //     ->getStateUsing(fn ($record) => $record->price_with_vat),
                // Tables\Columns\TextColumn::make('vat')
                //     ->suffix('%')
                //     ->sortable()
                //     ->label('VAT'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                // Tables\Columns\TextColumn::make('specifications_count')
                //     ->label('Specs')
                //     ->getStateUsing(fn ($record) => $record->specifications ? count($record->specifications) : 0)
                //     ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->visible(fn () => Auth::user()->hasRole('Super Admin')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\TextInput::make('year_from')
                            ->numeric()
                            ->placeholder('From year'),
                        Forms\Components\TextInput::make('year_to')
                            ->numeric()
                            ->placeholder('To year'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year_from'],
                                fn (Builder $query, $year): Builder => $query->where('year', '>=', $year),
                            )
                            ->when(
                                $data['year_to'],
                                fn (Builder $query, $year): Builder => $query->where('year', '<=', $year),
                            );
                    }),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->numeric()
                            ->placeholder('Min price'),
                        Forms\Components\TextInput::make('price_to')
                            ->numeric()
                            ->placeholder('Max price'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    private static function getCategoriesHierarchy(): array
    {
        $categories = Category::orderBy('level')->orderBy('name')->get();
        $hierarchy = [];

        foreach ($categories as $category) {
            $indent = str_repeat('— ', $category->level);
            $hierarchy[$category->id] = $indent . $category->name;
        }

        return $hierarchy;
    }

    private static function populateCategoryLevels($category, callable $set)
    {
        // Populate level 1 (main category)
        if ($category->level == 0) {
            $set('category_level_1', $category->id);
            $set('category_id', $category->id);
        }
        // Populate level 2 (subcategory)
        elseif ($category->level == 1) {
            $set('category_level_1', $category->root_id);
            $set('category_level_2', $category->id);
            $set('category_id', $category->id);
        }
        // Populate level 3 (sub-subcategory)
        elseif ($category->level == 2) {
            $parent = Category::find($category->root_id);
            if ($parent) {
                $set('category_level_1', $parent->root_id);
                $set('category_level_2', $category->root_id);
                $set('category_level_3', $category->id);
                $set('category_id', $category->id);
            }
        }
    }
}
