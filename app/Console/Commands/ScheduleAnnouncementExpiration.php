<?php

namespace App\Console\Commands;

use App\Jobs\ExpireAnnouncementsJob;
use Illuminate\Console\Command;

class ScheduleAnnouncementExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announcements:schedule-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule automatic expiration of announcements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scheduling automatic announcement expiration...');

        // Dispatch the job for immediate execution
        ExpireAnnouncementsJob::dispatch();

        $this->info('Automatic expiration job dispatched successfully.');
        $this->info('The job will run every hour automatically.');

        return 0;
    }
}
