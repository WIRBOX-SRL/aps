<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AnnouncementStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.announcement-stats-widget';

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
        $publishedCount = Announcement::whereIn('user_id', $userIds)->where('status', 'published')->count();
        return [
            'publishedCount' => $publishedCount,
        ];
    }
}
