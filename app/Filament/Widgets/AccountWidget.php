<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

class AccountWidget extends BaseAccountWidget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected static string $view = 'filament.widgets.account-widget-custom';

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'extra' => 'Hi, ' . auth()->user()->name . '!',
            // Poți adăuga aici orice date vrei să trimiți în view
        ]);
    }
}
