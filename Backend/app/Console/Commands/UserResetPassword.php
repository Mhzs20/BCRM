<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UserResetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password {mobile} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the password for a user by their mobile number.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mobile = $this->argument('mobile');
        $password = $this->argument('password');

        $user = \App\Models\User::where('mobile', $mobile)->first();

        if (!$user) {
            $this->error('User with mobile number ' . $mobile . ' not found.');
            return 1;
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($password);
        $user->save();

        $this->info('Password for user ' . $user->name . ' has been reset successfully.');
        return 0;
    }
}
