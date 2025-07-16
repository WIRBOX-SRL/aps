<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class TestUserSmtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:user-smtp {user_id} {--email= : Email to send test to (defaults to user email)} {--dry-run : Test configuration without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP configuration for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $testEmail = $this->option('email');
        $dryRun = $this->option('dry-run');

        // Find the user
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found.");
            return 1;
        }

        $this->info("🔍 Testing SMTP configuration for user: {$user->name} (ID: {$user->id})");

        // Get email settings for the user
        $emailSettings = $user->getEmailSettings();

        // Check if SMTP is configured
        if (!$this->hasSmtpConfigured($emailSettings)) {
            $this->warn("⚠️  User {$user->name} has no SMTP configuration. Checking admin...");

            // If user has an admin, check admin's settings
            if ($user->hasRole('User') && $user->creator) {
                $adminSettings = $user->creator->getEmailSettings();
                if (!$this->hasSmtpConfigured($adminSettings)) {
                    $this->error("❌ Neither user nor admin has SMTP configured.");
                    return 1;
                }
                $emailSettings = $adminSettings;
                $this->info("✅ Using admin's SMTP configuration.");
            } else {
                $this->error("❌ No SMTP configuration found.");
                return 1;
            }
        }

        // Display current configuration
        $this->displayConfiguration($emailSettings);

        if ($dryRun) {
            $this->info("🔍 DRY RUN - Configuration validation completed successfully!");
            return 0;
        }

        // Configure mail settings temporarily
        $this->configureMailSettings($emailSettings);

        // Set test email destination
        $recipientEmail = $testEmail ?: $user->email;

        $this->info("📧 Sending test email to: {$recipientEmail}");

        try {
            // Send test email
            Mail::raw("This is a test email from {$user->name}'s SMTP configuration.\n\nSent at: " . now()->format('Y-m-d H:i:s'), function (Message $message) use ($recipientEmail, $user, $emailSettings) {
                $message->to($recipientEmail)
                       ->subject("SMTP Test - {$user->name}")
                       ->from($emailSettings['smtp_username'], $user->name); // Use SMTP username as sender
            });

            $this->info("✅ Test email sent successfully!");
            $this->info("📬 Check the inbox at: {$recipientEmail}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send test email: " . $e->getMessage());
            Log::error("SMTP test failed for user {$user->id}: " . $e->getMessage());

            // Show detailed error for debugging
            $this->newLine();
            $this->error("🔧 Debug information:");
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());

            return 1;
        }
    }

    private function hasSmtpConfigured($settings): bool
    {
        return !empty($settings['smtp_host']) &&
               !empty($settings['smtp_username']) &&
               !empty($settings['smtp_password']);
    }

    private function displayConfiguration($settings): void
    {
        $this->info("📋 SMTP Configuration:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Host', $settings['smtp_host'] ?? 'Not set'],
                ['Username', $settings['smtp_username'] ?? 'Not set'],
                ['Password', $settings['smtp_password'] ? '***' . substr($settings['smtp_password'], -3) : 'Not set'],
                ['Encryption', $settings['smtp_encryption'] ?? 'Not set'],
                ['Port', $settings['smtp_port'] ?? 'Not set'],
            ]
        );
    }

    private function configureMailSettings($settings): void
    {
        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $settings['smtp_host'],
                'port' => $settings['smtp_port'] ?? 587,
                'encryption' => $settings['smtp_encryption'] ?? 'tls',
                'username' => $settings['smtp_username'],
                'password' => $settings['smtp_password'],
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            'mail.from' => [
                'address' => $settings['smtp_username'],
                'name' => config('app.name'),
            ],
        ]);

        // Clear any cached mail configuration
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
    }
}
