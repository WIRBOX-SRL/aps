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
        // Update the link with the real data after saving
        $record = $this->record;
        $record->load(['vehicle.category', 'vehicle']);

        $slug = $this->generateSlugFromRecord($record);
        $baseUrl = config('app.url');

        $record->update([
            'link' => "{$baseUrl}/announcements/{$record->id}/{$slug}"
        ]);
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
}
