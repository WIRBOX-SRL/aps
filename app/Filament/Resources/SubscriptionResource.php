<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Settings';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super Admin vede toate abonamentele
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        // Admin vede abonamentele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $query->whereHas('user', function ($q) {
                $q->where('created_by', Auth::id());
            });
        }

        // User vede doar abonamentul său
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
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Subscription', 'view');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        return Auth::user()->canAccessResourceBasedOnAdminSubscription('Subscription', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Subscription', 'edit')) {
            return false;
        }

        // Super Admin poate edita orice abonament
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate edita abonamentele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user->created_by === Auth::id();
        }

        // User poate edita doar abonamentul său
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
        if (!Auth::user()->canAccessResourceBasedOnAdminSubscription('Subscription', 'delete')) {
            return false;
        }

        // Super Admin poate șterge orice abonament
        if (Auth::user()->hasRole('Super Admin')) {
            return true;
        }

        // Admin poate șterge abonamentele utilizatorilor săi
        if (Auth::user()->hasRole('Admin')) {
            return $record->user->created_by === Auth::id();
        }

        // User poate șterge doar abonamentul său
        return $record->user_id === Auth::id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->preload(5)
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload(5)
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('subscription_type')
                            ->options([
                                'daily' => 'Daily',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->required()
                            ->default('monthly'),
                        Forms\Components\Select::make('stripe_status')->label('Status')
                            ->options([
                                'active' => 'Active',
                                'canceled' => 'Canceled',
                                'past_due' => 'Past Due',
                                'unpaid' => 'Unpaid',
                            ])
                            ->required()
                            ->default('active'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Expires At'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('subscription_type')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'yearly',
                        'warning' => 'daily',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'canceled',
                        'warning' => 'past_due',
                        'gray' => 'unpaid',
                        'gray' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_type')
                    ->options([
                        'daily' => 'Daily',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'canceled' => 'Canceled',
                        'past_due' => 'Past Due',
                        'unpaid' => 'Unpaid',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => $record->stripe_status === 'active')
                    ->action(function ($record) {
                        $record->renew();
                        Notification::make()
                            ->title('Subscription renewed')
                            ->body('Subscription has been renewed successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Renew Subscription')
                    ->modalDescription('Are you sure you want to renew this subscription?')
                    ->modalSubmitActionLabel('Renew'),

                Action::make('upgrade')
                    ->label('Upgrade Plan')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->visible(fn ($record) => $record->stripe_status === 'active')
                    ->form([
                        Forms\Components\Select::make('new_plan_id')
                            ->label('New Plan')
                            ->options(Plan::all()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->upgradeToPlan($data['new_plan_id']);
                        Notification::make()
                            ->title('Plan upgraded')
                            ->body('Subscription plan has been upgraded successfully.')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Upgrade Plan')
                    ->modalDescription('Select the new plan for this subscription.')
                    ->modalSubmitActionLabel('Upgrade'),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->stripe_status === 'active')
                    ->action(function ($record) {
                        $record->cancel();
                        Notification::make()
                            ->title('Subscription canceled')
                            ->body('Subscription has been canceled successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('Are you sure you want to cancel this subscription? This action cannot be undone.')
                    ->modalSubmitActionLabel('Cancel'),

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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
