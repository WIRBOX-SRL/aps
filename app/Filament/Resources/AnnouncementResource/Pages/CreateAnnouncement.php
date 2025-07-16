<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Generate a temporary link - will be updated after creation
        $data['link'] = $this->generateTemporaryLink();

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Check if user can create more announcements
        $currentCount = \App\Models\Announcement::where('user_id', Auth::id())->count();

        if (!Auth::user()->canCreateMoreOfResourceBasedOnAdminSubscription('Announcement', $currentCount)) {
            Notification::make()
                ->title('Announcement limit reached')
                ->body('You have reached your announcement creation limit or your admin subscription has expired.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        // Update the link with the real data after creation
        $record = $this->record;
        $record->load(['vehicle.category', 'vehicle']);

        $slug = $this->generateSlugFromRecord($record);
        $baseUrl = config('app.url');

        $record->update([
            'link' => "{$baseUrl}/announcements/{$record->id}/{$slug}"
        ]);
    }

    private function generateTemporaryLink(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/announcements/new/announcement";
    }

    private function generateSlugFromRecord($record): string
    {
        // Try to create a meaningful slug from available data
        $slugParts = [];

        if ($record->vehicle) {
            if ($record->vehicle->category) {
                $slugParts[] = $record->vehicle->category->name;
            }
            if ($record->vehicle->brand) {
                $slugParts[] = $record->vehicle->brand;
            }
            if ($record->vehicle->model) {
                $slugParts[] = $record->vehicle->model;
            }
        }

        if (empty($slugParts)) {
            $slugParts[] = 'announcement';
        }

        return Str::slug(implode('-', $slugParts));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
