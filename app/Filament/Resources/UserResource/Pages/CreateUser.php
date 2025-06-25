<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Auth::user();

        // Dacă Super Admin creează un utilizator
        if ($currentUser->hasRole('Super Admin')) {
            // Dacă a fost selectat un admin, setează created_by
            if (isset($data['admin_id']) && $data['admin_id']) {
                $data['created_by'] = $data['admin_id'];

                // Verifică dacă adminul poate crea mai mulți utilizatori
                $admin = User::find($data['admin_id']);
                if (!$admin->canCreateMoreUsersBasedOnAdminSubscription()) {
                    Notification::make()
                        ->title('Admin limit reached')
                        ->body("The selected admin has reached their user limit or doesn't have an active subscription.")
                        ->danger()
                        ->send();
                    $this->halt();
                }
            }

            // Dacă a fost selectat un plan pentru admin
            if (isset($data['plan_id']) && $data['plan_id'] && isset($data['admin_id']) && $data['admin_id']) {
                $admin = User::find($data['admin_id']);
                $plan = Plan::find($data['plan_id']);

                // Creează sau actualizează abonamentul pentru admin
                Subscription::updateOrCreate(
                    ['user_id' => $admin->id],
                    [
                        'plan_id' => $plan->id,
                        'name' => $plan->name,
                        'subscription_type' => 'monthly',
                        'ends_at' => $data['subscription_ends_at'] ?? now()->addMonth(),
                        'stripe_status' => 'active',
                    ]
                );
            }
        }

        // Dacă Admin creează un utilizator
        if ($currentUser->hasRole('Admin')) {
            $data['created_by'] = $currentUser->id;

            if (!$currentUser->canCreateMoreUsersBasedOnAdminSubscription()) {
                Notification::make()
                    ->title('User limit reached')
                    ->body("You have reached your user limit or don't have an active subscription.")
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
