<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MigrateAvatarsToCloudinary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:avatars-to-cloudinary {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing avatars from local storage to Cloudinary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        $users = User::whereNotNull('avatar_url')->get();

        if ($users->isEmpty()) {
            $this->info('No users with avatars found.');
            return 0;
        }

        $this->info("Found {$users->count()} users with avatars to migrate");

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                // Check if user has Cloudinary configured
                if (!$user->getAdminForUpload()->hasCloudinaryConfigured()) {
                    $this->newLine();
                    $this->warn("⚠️  User {$user->name} (ID: {$user->id}) - Admin has no Cloudinary configured, skipping");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $avatarPath = $user->avatar_url;

                // Check if avatar exists in local storage
                if (!Storage::disk('public')->exists($avatarPath)) {
                    $this->newLine();
                    $this->warn("⚠️  Avatar not found in local storage: {$avatarPath}");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if (!$dryRun) {
                    // Configure Cloudinary for this user
                    $this->configureCloudinaryForUser($user);

                    // Get the file content
                    $fileContent = Storage::disk('public')->get($avatarPath);
                    $fileName = basename($avatarPath);

                    // Upload to Cloudinary
                    $cloudinaryPath = Storage::disk('cloudinary')->put("avatars/{$fileName}", $fileContent);

                    if ($cloudinaryPath) {
                        // Update user's avatar_url to point to the new Cloudinary path
                        $user->update(['avatar_url' => $cloudinaryPath]);

                        // Optionally delete from local storage
                        // Storage::disk('public')->delete($avatarPath);

                        $migrated++;
                    } else {
                        $this->newLine();
                        $this->error("❌ Failed to upload {$avatarPath} to Cloudinary");
                        $errors++;
                    }
                } else {
                    $this->newLine();
                    $this->info("🔍 Would migrate: {$avatarPath} for user {$user->name}");
                    $migrated++;
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error migrating avatar for user {$user->name}: " . $e->getMessage());
                Log::error("Avatar migration error for user {$user->id}: " . $e->getMessage());
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Migration Summary:');
        $this->info("✅ Migrated: {$migrated}");
        $this->info("⚠️  Skipped: {$skipped}");
        $this->info("❌ Errors: {$errors}");

        if ($dryRun) {
            $this->newLine();
            $this->info('To actually perform the migration, run the command without --dry-run');
        }

        return 0;
    }

    private function configureCloudinaryForUser(User $user)
    {
        $adminForUpload = $user->getAdminForUpload();
        $cloudSettings = $adminForUpload->getCloudSettings();

        config([
            'cloudinary.cloud_url' => 'cloudinary://' . $cloudSettings['cloudinary_api_key'] . ':' . $cloudSettings['cloudinary_api_secret'] . '@' . $cloudSettings['cloudinary_cloud_name'],
            'cloudinary.cloud' => $cloudSettings['cloudinary_cloud_name'],
            'cloudinary.key' => $cloudSettings['cloudinary_api_key'],
            'cloudinary.secret' => $cloudSettings['cloudinary_api_secret'],
            'filesystems.disks.cloudinary.cloud' => $cloudSettings['cloudinary_cloud_name'],
            'filesystems.disks.cloudinary.key' => $cloudSettings['cloudinary_api_key'],
            'filesystems.disks.cloudinary.secret' => $cloudSettings['cloudinary_api_secret'],
        ]);

        // Purge the disk cache
        Storage::forgetDisk('cloudinary');
    }
}
