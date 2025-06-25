<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('renew')
                ->label('Renew')
                ->action(function () {
                    $this->record->ends_at = now()->addMonth();
                    $this->record->save();
                    $this->notify('success', 'Subscription renewed!');
                })
                ->visible(fn () => $this->record->ends_at && $this->record->ends_at < now()),
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('danger')
                ->action(function () {
                    $this->record->ends_at = now();
                    $this->record->save();
                    $this->notify('success', 'Subscription cancelled!');
                })
                ->visible(fn () => !$this->record->ends_at || $this->record->ends_at > now()),
        ];
    }
}
