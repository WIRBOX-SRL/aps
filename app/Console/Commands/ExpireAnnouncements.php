<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use Illuminate\Console\Command;

class ExpireAnnouncements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announcements:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire announcements that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired announcements...');

        $expiredAnnouncements = Announcement::where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredAnnouncements->isEmpty()) {
            $this->info('No announcements to expire.');
            return 0;
        }

        $count = 0;
        foreach ($expiredAnnouncements as $announcement) {
            $announcement->expire();
            $count++;
            $this->line("Expired announcement: {$announcement->id} - {$announcement->title}");
        }

        $this->info("Successfully expired {$count} announcement(s).");
        return 0;
    }
}
