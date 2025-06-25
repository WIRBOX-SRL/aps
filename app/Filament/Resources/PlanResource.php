<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Illuminate\Support\Str;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Settings';

    public static function canViewAny(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // Doar Super Admin și Admin pot vedea planurile
        return Auth::user()->hasAnyRole(['Super Admin', 'Admin']);
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // Doar Super Admin poate crea planuri
        return Auth::user()->hasRole('Super Admin');
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // Doar Super Admin poate edita planuri
        return Auth::user()->hasRole('Super Admin');
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // Doar Super Admin poate șterge planuri
        return Auth::user()->hasRole('Super Admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('user_limit')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Resource Permissions')
                    ->description('Define what resources and actions are available for this plan')
                    ->schema([
                        Forms\Components\Repeater::make('resources')
                            ->schema([
                                Forms\Components\Select::make('resource')
                                    ->options([
                                        'User' => 'User Management',
                                        'Vehicle' => 'Vehicle Management',
                                        'Announcement' => 'Announcement Management',
                                        'Seller' => 'Seller Management',
                                        'Category' => 'Category Management',
                                        'Plan' => 'Plan Management',
                                        'Subscription' => 'Subscription Management',
                                        'Country' => 'Country Management',
                                        'State' => 'State Management',
                                        'City' => 'City Management',
                                        'Currency' => 'Currency Management',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Reset permissions when resource changes
                                        $set('permissions', []);
                                        $set('create_limit', 0);
                                    }),
                                Forms\Components\CheckboxList::make('permissions')
                                    ->options([
                                        'view' => 'View',
                                        'create' => 'Create',
                                        'edit' => 'Edit',
                                        'delete' => 'Delete',
                                    ])
                                    ->columns(4)
                                    ->required()
                                    ->visible(fn ($get) => $get('resource')),
                                Forms\Components\TextInput::make('create_limit')
                                    ->label('Create Limit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Maximum number of creations for this resource (0 = unlimited)')
                                    ->default(0)
                                    ->visible(fn ($get) => $get('resource')),
                            ])
                            ->columns(3)
                            ->reorderable(false)
                            ->addActionLabel('Add Resource')
                            ->itemLabel(fn (array $state): ?string => $state['resource'] ?? null)
                            ->collapsible()
                            ->afterStateHydrated(function ($state, callable $set) {
                                // If state is in the wrong format (string keys), convert it to array format for Repeater
                                // But only if the keys are actual resource names, not UUIDs from new items
                                if (is_array($state) && !isset($state[0]) && !empty($state)) {
                                    // Check if the keys are resource names (not UUIDs)
                                    $resourceNames = ['User', 'Vehicle', 'Announcement', 'Seller', 'Category', 'Plan', 'Subscription', 'Country', 'State', 'City', 'Currency'];
                                    $hasResourceNames = false;

                                    foreach (array_keys($state) as $key) {
                                        if (in_array($key, $resourceNames)) {
                                            $hasResourceNames = true;
                                            break;
                                        }
                                    }

                                    if ($hasResourceNames) {
                                        $formData = [];
                                        foreach ($state as $resourceName => $resourceData) {
                                            $formData[] = [
                                                'resource' => $resourceName,
                                                'permissions' => $resourceData['permissions'] ?? [],
                                                'create_limit' => $resourceData['create_limit'] ?? 0,
                                            ];
                                        }

                                        // Set the converted data back to the repeater
                                        $set('resources', $formData);
                                    }
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_limit')
                    ->numeric()
                    ->sortable()
                    ->label('User Limit'),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Active Subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\Action::make('upgrade')
                //     ->label('Upgrade to this Plan')
                //     ->icon('heroicon-o-arrow-up')
                //     ->color('success')
                //     ->visible(fn () => Auth::user()->hasRole('Admin'))
                //     ->action(function (Plan $record) {
                //         // Logica pentru upgrade
                //         $user = Auth::user();

                //         // Verifică dacă utilizatorul are o abonare activă
                //         $activeSubscription = $user->subscriptions()
                //             ->where('status', 'active')
                //             ->first();

                //         if ($activeSubscription) {
                //             // Upgrade la planul nou
                //             $activeSubscription->update([
                //                 'plan_id' => $record->id,
                //                 'updated_at' => now(),
                //             ]);

                //             \Filament\Notifications\Notification::make()
                //                 ->title('Plan upgraded successfully')
                //                 ->body("Your subscription has been upgraded to {$record->name}")
                //                 ->success()
                //                 ->send();
                //         } else {
                //             \Filament\Notifications\Notification::make()
                //                 ->title('No active subscription')
                //                 ->body('You need an active subscription to upgrade')
                //                 ->danger()
                //                 ->send();
                //         }
                //     })
                //     ->requiresConfirmation()
                //     ->modalHeading(fn ($record) => 'Upgrade to ' . $record->name)
                //     ->modalDescription('Are you sure you want to upgrade to this plan? This will change your current subscription.')
                //     ->modalSubmitActionLabel('Upgrade'),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->hasRole('Super Admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make() ->visible(fn () => Auth::user()->hasRole('Super Admin')),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['resources'] = self::mapResourcesRepeater($data['resources'] ?? []);
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['resources'] = self::mapResourcesRepeater($data['resources'] ?? []);
        return $data;
    }

    // public static function mutateFormDataBeforeFill(array $data): array
    // {
    //     $data['resources'] = self::mapResourcesForForm($data['resources'] ?? []);
    //     return $data;
    // }

    private static function mapResourcesRepeater(array $repeater): array
    {
        if (empty($repeater)) {
            return [];
        }

        $resources = [];
        foreach ($repeater as $item) {
            if (isset($item['resource']) && isset($item['permissions'])) {
                $resources[] = [
                    'resource' => $item['resource'],
                    'permissions' => $item['permissions'],
                    'create_limit' => isset($item['create_limit']) ? (int)$item['create_limit'] : 0,
                ];
            }
        }
        return $resources;
    }

    private static function mapResourcesForForm(array $resources): array
    {
        if (empty($resources)) {
            return [];
        }

        $formData = [];

        // Verifică dacă este format cu chei string (Basic, Premium, Enterprise)
        if (is_array($resources) && !isset($resources[0])) {
            foreach ($resources as $resourceName => $resourceData) {
                $formData[] = [
                    'resource' => $resourceName,
                    'permissions' => $resourceData['permissions'] ?? [],
                    'create_limit' => $resourceData['create_limit'] ?? 0,
                ];
            }
        } else {
            // Format cu array numeric (Premium Admin Plan)
            foreach ($resources as $resourceData) {
                if (isset($resourceData['resource'])) {
                    $formData[] = [
                        'resource' => $resourceData['resource'],
                        'permissions' => $resourceData['permissions'] ?? [],
                        'create_limit' => $resourceData['create_limit'] ?? 0,
                    ];
                }
            }
        }

        return $formData;
    }
}
