<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Actualizează link-ul cu ID-ul real
        $record = $this->record;
        $slug = Str::slug($record->title ?? 'announcement');
        $baseUrl = config('app.url');

        $record->update([
            'link' => "{$baseUrl}/announcements/{$record->id}/{$slug}"
        ]);
    }
}
