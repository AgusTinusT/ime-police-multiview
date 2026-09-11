<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    protected $signature = 'make:admin 
                            {--name= : The name of the admin user} 
                            {--email= : The email of the admin user} 
                            {--password= : The password for the admin user}';

    protected $description = 'Create or update an admin user for the Tactical Multiview portal';

    public function handle(): int
    {
        $this->info('=== Tactical Multiview Admin Account Setup ===');

        $name = $this->option('name') ?: $this->ask('Enter admin name', 'Admin Dispatcher');
        $email = $this->option('email') ?: $this->ask('Enter admin email', 'admin@example.com');
        $password = $this->option('password') ?: $this->secret('Enter admin password');

        if (empty($password)) {
            $this->error('Password cannot be empty!');
            return 1;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($this->confirm("User with email [{$email}] already exists. Update password and name?", true)) {
                $user->update([
                    'name' => $name,
                    'password' => Hash::make($password),
                ]);
                $this->info("✓ Admin account [{$email}] successfully updated!");
                return 0;
            }
            $this->warn('Operation cancelled.');
            return 0;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("✓ Admin account created successfully!");
        $this->line("Email   : {$email}");
        $this->line("Name    : {$name}");
        $this->line("Login at: /login or /admin");

        return 0;
    }
}
