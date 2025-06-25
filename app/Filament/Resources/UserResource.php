<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use App\Models\Plan;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationColor = 'success';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super Admin vede toți utilizatorii
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        // Admin vede doar utilizatorii pe care i-a creat
        if (Auth::user()->hasRole('Admin')) {
            return $query->where('created_by', Auth::id());
        }

        // User nu vede niciun utilizator
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
        // User nu poate accesa resursa User
        if (Auth::user()->hasRole('User')) {
            return false;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        if (!Auth::user()->canCreateMoreUsersBasedOnAdminSubscription()) {
            return false;
        }
        // User nu poate crea utilizatori
        if (Auth::user()->hasRole('User')) {
            return false;
        }

        return true;
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // User nu poate edita utilizatori
        if (Auth::user()->hasRole('User')) {
            return false;
        }

        // Admin poate edita doar utilizatorii pe care i-a creat
        if (Auth::user()->hasRole('Admin')) {
            return $record->created_by === Auth::id();
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->is_suspended) {
            return false;
        }
        if (!Auth::user()->canAccessBasedOnAdminSubscription()) {
            return false;
        }
        // User nu poate șterge utilizatori
        if (Auth::user()->hasRole('User')) {
            return false;
        }

        // Admin poate șterge doar utilizatorii pe care i-a creat
        if (Auth::user()->hasRole('Admin')) {
            return $record->created_by === Auth::id();
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->visibleOn('create'),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->options(function () {
                                $roles = Role::query();

                                // Super Admin poate atribui orice rol
                                if (Auth::user()->hasRole('Super Admin')) {
                                    return $roles->pluck('name', 'id');
                                }

                                // Admin poate atribui doar rolul User
                                if (Auth::user()->hasRole('Admin')) {
                                    return $roles->where('name', 'User')->pluck('name', 'id');
                                }

                                return collect();
                            }),
                    ])->columns(2),

                Section::make('Subscription Management')
                    ->description('Manage subscription for this user (Super Admin only)')
                    ->visible(fn () => Auth::user()->hasRole('Super Admin'))
                    ->schema([
                        Select::make('admin_id')
                            ->label('Assign to Admin')
                            ->options(function () {
                                return User::whereHas('roles', function ($query) {
                                    $query->where('name', 'Admin');
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('Select admin to assign user to')
                            ->helperText('This admin will be responsible for this user'),

                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Select plan for admin')
                            ->helperText('This plan will be assigned to the selected admin'),

                        DatePicker::make('subscription_ends_at')
                            ->label('Subscription Ends At')
                            ->helperText('Leave empty for unlimited subscription'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_suspended')
                    ->label('Suspended')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Assigned By')
                    ->sortable()
                    ->visible(fn () => Auth::user()->hasRole('Super Admin')),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Subscription Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Filter by Role'),
                Tables\Filters\SelectFilter::make('creator')
                    ->relationship('creator', 'name')
                    ->label('Filter by Creator')
                    ->visible(fn () => Auth::user()->hasRole('Super Admin')),
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Suspended Status'),
            ])
            ->actions([
                Impersonate::make()
                    ->visible(function ($record) {
                        $user = Auth::user();

                        // Super Admin poate impersona pe toți
                        if ($user->hasRole('Super Admin')) {
                            return true;
                        }

                        // Admin poate impersona doar utilizatorii pe care i-a creat
                        if ($user->hasRole('Admin')) {
                            return $record->created_by === $user->id;
                        }

                        return false;
                    })
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->label('Impersonate'),
                Action::make('toggleSuspend')
                    ->label(fn ($record) => $record->is_suspended ? 'Unsuspend' : 'Suspend')
                    ->icon(fn ($record) => $record->is_suspended ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn ($record) => $record->is_suspended ? 'success' : 'danger')
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if ($user->hasRole('Super Admin')) {
                            return true;
                        }
                        if ($user->hasRole('Admin')) {
                            return $record->created_by === $user->id;
                        }
                        return false;
                    })
                    ->action( function ($record) {
                        if ($record->is_suspended) {
                            $record->unsuspendWithCascade();
                            $message = 'User and all associated users have been unsuspended.';
                        } else {
                            $record->suspendWithCascade();
                            $message = 'User and all associated users have been suspended.';
                        }

                        // Notificare Filament
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_suspended ? 'User suspended' : 'User unsuspended')
                            ->body($message)
                            ->success()
                            ->sendToDatabase($record);

                        // Notificare email doar la suspendare
                        if ($record->is_suspended) {
                            \Mail::to($record->email)->send(new \App\Mail\UserSuspendedMail($record));
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_suspended ? 'Unsuspend User' : 'Suspend User')
                    ->modalDescription(fn ($record) => $record->is_suspended
                        ? 'Are you sure you want to unsuspend this user and all associated users?'
                        : 'Are you sure you want to suspend this user and all associated users? This action will also suspend all users created by this user.')
                    ->modalSubmitActionLabel(fn ($record) => $record->is_suspended ? 'Unsuspend' : 'Suspend'),
                Tables\Actions\EditAction::make()->icon('heroicon-m-pencil')->label(false),
                Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash')->label(false),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
