<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'app:create-admin-user-command';

    protected $description = 'Creat an admin user';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        $user = User::updateOrCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'admin' => true,
        ]);

        if ($user->wasRecentlyCreated) {
            $this->info('Admin user created successfully');

            return 0;
        }

        return 0;
    }
}
