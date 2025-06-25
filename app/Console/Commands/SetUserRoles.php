<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetUserRoles extends Command
{
    protected $signature = 'users:set-roles {--id=* : ID-urile userilor} {--email=* : Email-urile userilor} {--role= : Rolul de setat (superadmin, admin, user)}';
    protected $description = 'Quickly set roles for existing users by id or email';

    public function handle()
    {
        $role = $this->option('role');
        if (!in_array($role, ['superadmin', 'admin', 'user'])) {
            $this->error('Invalid role. Use: superadmin, admin or user.');
            return 1;
        }
        $ids = $this->option('id');
        $emails = $this->option('email');
        $query = User::query();
        if ($ids) {
            $query->orWhereIn('id', $ids);
        }
        if ($emails) {
            $query->orWhereIn('email', $emails);
        }
        $users = $query->get();
        if ($users->isEmpty()) {
            $this->info('No users found for the given criteria.');
            return 0;
        }
        foreach ($users as $user) {
            $user->role = $role;
            $user->save();
            $this->info("Setat rolul '$role' pentru user: {$user->email} (ID: {$user->id})");
        }
        $this->info('Update complete!');
        return 0;
    }
}
