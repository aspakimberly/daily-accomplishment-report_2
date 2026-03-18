<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('auth:create-super-admin {email} {name?}', function (string $email, ?string $name = null) {
    $email = strtolower(trim($email));
    $name = $name !== null && trim($name) !== ''
        ? trim($name)
        : (string) str($email)->before('@')->replace(['.', '_', '-'], ' ')->title();

    $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

    if ($existingUser) {
        if ($existingUser->role !== 'super_admin' || $existingUser->status !== 'active') {
            $existingUser->forceFill([
                'name' => $existingUser->name ?: $name,
                'role' => 'super_admin',
                'status' => 'active',
                'password' => $existingUser->password ?: Hash::make((string) str()->random(32)),
            ])->save();

            $this->info("Existing user promoted to active super_admin: {$email}");

            return self::SUCCESS;
        }

        $this->warn("An active super_admin already exists for {$email}.");

        return self::SUCCESS;
    }

    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make((string) str()->random(32)),
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->info("Super admin created: {$email}");

    return self::SUCCESS;
})->purpose('Bootstrap the first active super_admin account for OTP login');
