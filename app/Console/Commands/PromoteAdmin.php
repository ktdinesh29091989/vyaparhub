<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote {email} {--revoke : Remove admin access instead of granting it} {--force : Skip the confirmation prompt}';

    protected $description = 'Grant (or, with --revoke, remove) admin access for a user by exact email match';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        return $this->option('revoke') ? $this->revoke($user) : $this->grant($user);
    }

    private function grant(User $user): int
    {
        if ($user->is_admin) {
            $this->info("{$user->name} ({$user->email}) is already an admin — nothing to do.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Grant admin access to {$user->name} ({$user->email})?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info("Granted admin access to {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }

    private function revoke(User $user): int
    {
        if (! $user->is_admin) {
            $this->info("{$user->name} ({$user->email}) is not an admin — nothing to do.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Revoke admin access for {$user->name} ({$user->email})?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => false])->save();
        $this->info("Revoked admin access for {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }
}
