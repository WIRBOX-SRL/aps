<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeDelete(array $data): array
    {
        $planId = $this->record->id;
        // Șterge subscripțiile asociate
        \App\Models\Subscription::where('plan_id', $planId)->each(function($subscription) {
            // Șterge userul asociat subscripției
            $user = $subscription->user;
            if ($user) {
                $user->delete();
            }
            $subscription->delete();
        });
        return $data;
    }
}
