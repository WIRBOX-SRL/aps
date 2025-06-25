<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;


class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Impersonate::make()->record($this->getRecord())
        ];
    }

    protected function afterSave(): void
    {
        $currentUser = Auth::user();
        $user = $this->getRecord();
        $data = $this->form->getState();

        // Dacă Super Admin modifică un utilizator
        if ($currentUser->hasRole('Super Admin')) {
            // Dacă a fost modificat adminul responsabil
            if (isset($data['admin_id']) && $data['admin_id']) {
                $user->update(['created_by' => $data['admin_id']]);
            }

            // Dacă a fost modificat planul pentru admin
            if (isset($data['plan_id']) && $data['plan_id'] && isset($data['admin_id']) && $data['admin_id']) {
                $admin = User::find($data['admin_id']);
                $plan = Plan::find($data['plan_id']);

                if ($admin && $plan) {
                    Subscription::updateOrCreate(
                        ['user_id' => $admin->id],
                        [
                            'plan_id' => $plan->id,
                            'name' => $plan->name,
                            'ends_at' => $data['subscription_ends_at'] ?? null,
                            'stripe_status' => 'active',
                        ]
                    );
                }
            }
        }
    }
}
