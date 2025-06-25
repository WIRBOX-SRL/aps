<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AnnouncementsPerUserWidget extends Widget
{
    protected static string $view = 'filament.widgets.announcements-per-user-widget';

    public function getViewData(): array
    {
        $user = Auth::user();
        $data = [];
        if ($user->hasRole('Super Admin')) {
            $users = User::all();
        } elseif ($user->hasRole('Admin')) {
            $users = User::where('created_by', $user->id)->orWhere('id', $user->id)->get();
        } else {
            $users = collect();
        }
        foreach ($users as $u) {
            $data[] = [
                'name' => $u->name,
                'count' => Announcement::where('user_id', $u->id)->count(),
            ];
        }
        return [
            'users' => $data,
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('Admin') || $user->hasRole('Super Admin'));
    }
}
