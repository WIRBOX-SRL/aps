<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
// Presupunem ca exista un model Setting
use App\Models\Setting;

class FilamentInfoWidget extends Widget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected static string $view = 'filament.widgets.custom-filament-info-widget';

    protected function getViewData(): array
    {
        // Ia toti superadminii
        $superadmins = \App\Models\User::role('Super Admin')->get(['name', 'email', 'phone']);
        return [
            'superadmins' => $superadmins,
        ];
    }
}
