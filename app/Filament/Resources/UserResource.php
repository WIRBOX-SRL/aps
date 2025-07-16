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
                Action::make('testSmtp')
                    ->label('Test SMTP')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
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
                    ->form([
                        \Filament\Forms\Components\TextInput::make('test_email')
                            ->label('Test Email Address')
                            ->email()
                            ->default(fn ($record) => $record->email)
                            ->required()
                            ->helperText('Email address where the test email will be sent'),
                        \Filament\Forms\Components\Toggle::make('dry_run')
                            ->label('Dry Run (Test Configuration Only)')
                            ->helperText('Only validate configuration without sending actual email')
                            ->default(false),
                    ])
                    ->action(function ($record, array $data) {
                        // Get email settings for the user
                        $emailSettings = $record->getEmailSettings();

                        // Check if SMTP is configured
                        if (!self::hasSmtpConfigured($emailSettings)) {
                            // If user has an admin, check admin's settings
                            if ($record->hasRole('User') && $record->creator) {
                                $adminSettings = $record->creator->getEmailSettings();
                                if (!self::hasSmtpConfigured($adminSettings)) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('SMTP Not Configured')
                                        ->body('Neither user nor admin has SMTP configuration.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                $emailSettings = $adminSettings;
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('SMTP Not Configured')
                                    ->body('No SMTP configuration found for this user.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        if ($data['dry_run']) {
                            \Filament\Notifications\Notification::make()
                                ->title('SMTP Configuration Valid')
                                ->body('SMTP configuration is properly set up.')
                                ->success()
                                ->send();
                            return;
                        }

                        try {
                            // Configure mail settings temporarily
                            self::configureMailSettings($emailSettings);

                            // Send test email
                            \Illuminate\Support\Facades\Mail::raw(
                                "This is a test email from {$record->name}'s SMTP configuration.\n\nSent at: " . now()->format('Y-m-d H:i:s'),
                                function (\Illuminate\Mail\Message $message) use ($data, $record, $emailSettings) {
                                    $message->to($data['test_email'])
                                           ->subject("SMTP Test - {$record->name}")
                                           ->from($emailSettings['smtp_username'], $record->name); // Use SMTP username as sender
                                }
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Test Email Sent')
                                ->body("Test email sent successfully to {$data['test_email']}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('SMTP Test Failed')
                                ->body("Failed to send test email: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Test SMTP Configuration')
                    ->modalDescription('Send a test email to verify SMTP configuration')
                    ->modalSubmitActionLabel('Send Test Email'),
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

    private static function hasSmtpConfigured($settings): bool
    {
        return !empty($settings['smtp_host']) &&
               !empty($settings['smtp_username']) &&
               !empty($settings['smtp_password']);
    }

    private static function configureMailSettings($settings): void
    {
        \Illuminate\Support\Facades\Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $settings['smtp_host'],
                'port' => $settings['smtp_port'] ?? 587,
                'encryption' => $settings['smtp_encryption'] ?? 'tls',
                'username' => $settings['smtp_username'],
                'password' => $settings['smtp_password'],
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            'mail.from' => [
                'address' => $settings['smtp_username'],
                'name' => config('app.name'),
            ],
        ]);

        // Clear any cached mail configuration
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }
}
