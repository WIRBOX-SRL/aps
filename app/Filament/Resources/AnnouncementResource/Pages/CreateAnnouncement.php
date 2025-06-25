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

        // Generează link-ul automat
        $data['link'] = $this->generateAnnouncementLink($data);

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Verifică dacă utilizatorul poate crea mai multe anunțuri
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
        // Actualizează link-ul cu ID-ul real
        $record = $this->record;
        $slug = \Str::slug($record->title ?? 'announcement');
        $baseUrl = config('app.url');

        $record->update([
            'link' => "{$baseUrl}/announcements/{$record->id}/{$slug}"
        ]);
    }

    private function generateAnnouncementLink(array $data): string
    {
        $baseUrl = config('app.url');
        $slug = Str::slug($data['title'] ?? 'announcement');
        $id = $data['id'] ?? 'new';

        return "{$baseUrl}/announcements/{$id}/{$slug}";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
