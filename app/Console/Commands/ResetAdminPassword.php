<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password {email : Email of the admin account} {--activate : Also re-activate the account if it is deactivated}';

    protected $description = 'Reset an admin user\'s password from the server (recovery path when no other admin can do it from the panel)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('New password');
        $confirmation = (string) $this->secret('Confirm new password');

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'confirmed', Password::defaults()]]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $attributes = ['password' => Hash::make($password)];

        if ($this->option('activate')) {
            $attributes['is_active'] = true;
        }

        $user->update($attributes);

        $this->info("Password updated for user #{$user->id}." . ($this->option('activate') ? ' Account activated.' : ''));

        return self::SUCCESS;
    }
}
