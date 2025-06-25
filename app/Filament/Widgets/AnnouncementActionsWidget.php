<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
// Presupunem ca exista un model AnnouncementAction
use App\Models\AnnouncementAction;

class AnnouncementActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.announcement-actions-widget';

    public function getViewData(): array
    {
        $user = Auth::user();
        if ($user->hasRole('Super Admin')) {
            $users = User::all();
        } elseif ($user->hasRole('Admin')) {
            $users = User::where('created_by', $user->id)->orWhere('id', $user->id)->get();
        } else {
            $users = collect();
        }
        $userIds = $users->pluck('id');
        $announcementIds = Announcement::whereIn('user_id', $userIds)->pluck('id');
        $actionsPerAnnouncement = Announcement::whereIn('announcement_id', $announcementIds)->count();
        return [
            'actionsPerAnnouncement' => $actionsPerAnnouncement,
        ];
    }
}
