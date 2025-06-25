<?php

namespace App\Jobs;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireAnnouncementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting automatic announcement expiration job...');

        $expiredAnnouncements = Announcement::where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredAnnouncements->isEmpty()) {
            Log::info('No announcements to expire.');
            return;
        }

        $count = 0;
        foreach ($expiredAnnouncements as $announcement) {
            $announcement->expire();
            $count++;
            Log::info("Automatically expired announcement: {$announcement->id} - {$announcement->title}");
        }

        Log::info("Automatic expiration job completed. Expired {$count} announcement(s).");
    }

    /**
     * Schedule the job to run every hour
     */
    public static function schedule()
    {
        return static::dispatch()->hourly();
    }
}
