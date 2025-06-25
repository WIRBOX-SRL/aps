<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\Announcement;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Vehicles';
    protected static ?int $navigationSort = 1;


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->select([
                'announcements.id',
                'announcements.pin',
                'announcements.title',
                'announcements.price',
                'announcements.vat',
                'announcements.currency_id',
                'announcements.user_id',
                'announcements.seller_id',
                'announcements.vehicle_id',
                'announcements.country_id',
                'announcements.state_id',
                'announcements.city_id',
                'announcements.link',
                'announcements.status',
                'announcements.published_at',
                'announcements.expires_at',
                'announcements.created_at',
                'announcements.max_ip_access_count',
                'announcements.allowed_ips',
            ]);

        // Super Admin vede toate anunțurile
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        // Admin vede anunțurile create de el și anunțurile utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $query->where(function ($q) {
                $q->where('user_id', Auth::id()) // Anunțurile create de Admin
                  ->orWhereHas('user', function ($subQ) {
                      $subQ->where('created_by', Auth::id()); // Anunțurile utilizatorilor săi
                  });
            });
        }

        // User vede doar anunțurile sale
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
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Announcement', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Announcement', 'create')) {
            return false;
        }

        // Verifică limita de creații
        $currentCount = Announcement::where('user_id', Auth::id())->count();
        return Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Announcement', $currentCount);
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Announcement', 'edit')) {
            return false;
        }

        // Super Admin poate edita orice anunț
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate edita anunțurile create de el și anunțurile utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user_id === Auth::id() || // Anunțurile create de Admin
                   ($record->user && $record->user->created_by === Auth::id()); // Anunțurile utilizatorilor săi
        }

        // User poate edita doar anunțurile sale
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
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Announcement', 'delete')) {
            return false;
        }

        // Super Admin poate șterge orice anunț
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate șterge anunțurile create de el și anunțurile utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user_id === Auth::id() || // Anunțurile create de Admin
                   ($record->user && $record->user->created_by === Auth::id()); // Anunțurile utilizatorilor săi
        }

        // User poate șterge doar anunțurile sale
        return $record->user_id === Auth::id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Information')
                    ->schema([
                        // Forms\Components\TextInput::make('title')
                        //     ->maxLength(255),
                        Forms\Components\TextInput::make('pin')
                            ->label('PIN Code')
                            ->maxLength(8)
                            ->minLength(8)
                            ->helperText('8-character unique PIN for purchase validation. Leave empty to auto-generate.')
                            ->placeholder('Auto-generated')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($state, $record) {
                                if ($record && $record->pin) {
                                    return $record->pin;
                                }
                                return 'Will be auto-generated';
                            }),
                        Forms\Components\Select::make('seller_id')
                        ->relationship('seller', 'name', function ($query) {
                            // Super Admin vede toți sellerii
                            if (Auth::user()->hasRole('Super Admin')) {
                                return $query->select('id', 'name');
                            }

                            // Admin vede sellerii săi și ai utilizatorilor săi
                            if (Auth::user()->hasRole('Admin')) {
                                return $query->where(function ($q) {
                                    $q->where('user_id', Auth::id()) // Sellerii Admin-ului
                                      ->orWhereHas('user', function ($subQ) {
                                          $subQ->where('created_by', Auth::id()); // Sellerii utilizatorilor săi
                                      });
                                })->select('id', 'name');
                            }

                            // User vede doar sellerii săi
                            if (Auth::user()->hasRole('User')) {
                                return $query->where('user_id', Auth::id())->select('id', 'name');
                            }

                            return $query->where('id', 0); // Nu vede nimic
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Select the seller for this announcement'),
                    Forms\Components\Select::make('vehicle_id')
                        ->relationship('vehicle', 'title', function ($query) {
                            // Super Admin vede toate vehiculele
                            if (Auth::user()->hasRole('Super Admin')) {
                                return $query->select('id', 'title');
                            }

                            // Admin vede vehiculele sale și ale utilizatorilor săi
                            if (Auth::user()->hasRole('Admin')) {
                                return $query->where(function ($q) {
                                    $q->where('user_id', Auth::id()) // Vehiculele Admin-ului
                                      ->orWhereHas('user', function ($subQ) {
                                          $subQ->where('created_by', Auth::id()); // Vehiculele utilizatorilor săi
                                      });
                                })->select('id', 'title');
                            }

                            // User vede doar vehiculele sale
                            if (Auth::user()->hasRole('User')) {
                                return $query->where('user_id', Auth::id())->select('id', 'title');
                            }

                            return $query->where('id', 0); // Nu vede nimic
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Select the vehicle for this announcement'),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('€')
                            ->helperText('Price without VAT'),
                        Forms\Components\TextInput::make('vat')
                            ->numeric()
                            ->suffix('%')
                            ->default(19.00)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('VAT percentage'),
                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->symbol})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the currency for this announcement'),
                        Forms\Components\Select::make('country_id')
                            ->relationship('country', 'name', function ($query) {
                                return $query->select('id', 'name');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('state_id', null);
                                $set('city_id', null);
                            })
                            ->helperText('Select the country'),
                        Forms\Components\Select::make('state_id')
                            ->relationship('state', 'name', function ($query, $get) {
                                $countryId = $get('country_id');
                                if ($countryId) {
                                    $query->where('country_id', $countryId)->select('id', 'name', 'country_id');
                                }
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('city_id', null);
                            })
                            ->helperText('Select the state/province'),
                        Forms\Components\Select::make('city_id')
                            ->relationship('city', 'name', function ($query, $get) {
                                $stateId = $get('state_id');
                                if ($stateId) {
                                    $query->where('state_id', $stateId)->select('id', 'name', 'state_id');
                                }
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Select the city'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('published')
                            ->required()
                            ->helperText('Set the status of this announcement'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->displayFormat('d.m.Y H:i')
                            ->format('Y-m-d H:i:s')
                            ->nullable()
                            ->helperText('Leave empty for no expiration. When expired, announcement becomes draft.'),
                            Forms\Components\TextInput::make('max_ip_access_count')
                            ->label('Max IP Access Count')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Maximum number of times each IP can access. 0 = unlimited.'),
                            Forms\Components\Repeater::make('allowed_ips')
                            ->label('Allowed IP Addresses')
                            ->schema([
                                Forms\Components\TextInput::make('ip')
                                    ->label('IP Address')
                                    ->required()
                                    ->placeholder('192.168.1.1')
                                    ->helperText('Enter IP addresses that can access this announcement')
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('Add IP Address')
                            ->helperText('Leave empty to allow all IPs. Add specific IPs to restrict access.'),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginationPageOptions([5, 10, 25, 50])
            ->columns([
                // Tables\Columns\TextColumn::make('title')
                //     ->searchable()
                //     ->sortable()
                //     ->limit(30),
                Tables\Columns\TextColumn::make('pin')
                    ->label('PIN')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('PIN copied to clipboard!')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('vehicle.title')
                ->label('Vehicle')
                ->sortable()
                ->searchable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Seller')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('EUR')
                    ->sortable()
                    ->label('Price (excl. VAT)'),
                Tables\Columns\TextColumn::make('price_with_vat')
                    ->money('EUR')
                    ->label('Price (incl. VAT)')
                    ->getStateUsing(fn ($record) => $record->price_with_vat),
                Tables\Columns\TextColumn::make('vat')
                    ->suffix('%')
                    ->sortable()
                    ->label('VAT'),
                // Tables\Columns\TextColumn::make('currency.name')
                //     ->badge()
                //     ->sortable()
                //     ->label('Currency'),
                Tables\Columns\TextColumn::make('currency.symbol')
                    ->badge()
                    ->sortable()
                    ->label('Symbol'),


                Tables\Columns\TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('state.name')
                //     ->label('State')
                //     ->sortable()
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('city.name')
                //     ->label('City')
                //     ->sortable()
                //     ->searchable(),

                // Tables\Columns\TextColumn::make('status')
                //     ->label('Status')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                    Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (!$record->getRawOriginal('expires_at')) {
                            return 'No expiration';
                        }
                        $formattedDate = \Illuminate\Support\Carbon::parse($record->getRawOriginal('expires_at'))->format('d-m-Y H:i');
                        if ($record->shouldBeExpired()) {
                            return $formattedDate . ' (EXPIRED)';
                        }
                        return $formattedDate;
                    }),
                Tables\Columns\TextColumn::make('ip_access_info')
                    ->label('IP Access')
                    ->getStateUsing(function ($record) {
                        $uniqueCount = $record->getUniqueIpAccessCount();
                        $totalCount = $record->getTotalIpAccessCount();
                        $maxCount = $record->max_ip_access_count;

                        if ($maxCount > 0) {
                            return "{$uniqueCount} IPs ({$totalCount}/{$maxCount})";
                        }
                        return "{$uniqueCount} IPs ({$totalCount} total)";
                    })
                    ->badge()
                    ->color('info'),
                // Tables\Columns\TextColumn::make('allowed_ips_count')
                //     ->label('Allowed IPs')
                //     ->getStateUsing(fn ($record) => count($record->allowed_ips ?? []))
                //     ->badge()
                //     ->color('warning'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable()
                    ->visible(fn () => Auth::user()->hasRole('Super Admin')),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\TernaryFilter::make('status')
                //     ->label('Status'),
                // Tables\Filters\SelectFilter::make('currency_id')
                //     ->relationship('currency', 'name')
                //     ->label('Currency'),
                Tables\Filters\Filter::make('published_at')
                    ->form([
                        Forms\Components\DatePicker::make('published_from')
                            ->placeholder('From date'),
                        Forms\Components\DatePicker::make('published_to')
                            ->placeholder('To date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date),
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

                Tables\Actions\Action::make('toggle-publish')
                ->label(fn ($record) => $record->isPublished() ? 'Unpublish' : 'Publish')
                ->icon(fn ($record) => $record->isPublished() ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->action(function ($record) {
                    if ($record->isPublished()) {
                        $record->update(['status' => 'draft']);
                    } else {
                        $record->publish();
                    }
                })
                ->color(fn ($record) => $record->isPublished() ? 'danger' : 'success'),
                Tables\Actions\Action::make('copy-link')
                ->label('Copy Link')
                ->icon('heroicon-o-clipboard')
                ->action(function ($record) {
                    $vehicle = $record->vehicle;
                    $category = $vehicle?->category?->name ?? 'category';
                    $brand = $vehicle?->brand ?? 'brand';
                    $model = $vehicle?->model ?? 'model';
                    $url = url('/' . Str::slug($category) . '/' . Str::slug($brand) . '/' . Str::slug($model));
                    Notification::make()
                        ->title('Link')
                        ->body("Link: <a href=\"{$url}\" target=\"_blank\">{$url}</a>")
                        ->success()
                        ->send();
                }),
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
