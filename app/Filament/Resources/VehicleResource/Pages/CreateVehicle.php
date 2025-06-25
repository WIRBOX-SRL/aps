<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Asigură-te că images este un array
        if (isset($data['images']) && is_array($data['images'])) {
            $data['images'] = array_filter($data['images']); // Elimină valorile goale
        }

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Verifică dacă utilizatorul poate crea mai multe vehicule
        $currentCount = \App\Models\Vehicle::where('user_id', Auth::id())->count();

        if (!Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Vehicle', $currentCount)) {
            Notification::make()
                ->title('Vehicle limit reached')
                ->body('You have reached your vehicle creation limit or your admin subscription has expired.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
