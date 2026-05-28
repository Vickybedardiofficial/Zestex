<?php

namespace Database\Seeders;

use App\Enums\F2AType;
use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotificationSettings;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'admin@Zestex.test';
        $adminPassword = 'admin@123';

        $admin = User::firstOrNew(['email' => $adminEmail]);
        if (! $admin->exists) {
            $admin->fill([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'role' => 'admin',
                'status' => 'active',
                'verified' => true,
                'email_verified_at' => now(),
                'tips' => [],
                'last_active' => now()->toDateTimeString(),
            ]);

            // Only set a known password on first creation to avoid resetting an existing admin.
            $admin->password = $adminPassword;
        } else {
            // Keep existing profile fields; just ensure the account stays usable.
            $admin->role = 'admin';
            if (empty($admin->status)) {
                $admin->status = 'active';
            }
        }
        $admin->save();
        $this->bootstrapUser($admin);

        $userEmail = 'user@Zestex.test';
        $userPassword = 'user@123';

        $user = User::firstOrNew(['email' => $userEmail]);
        if (! $user->exists) {
            $user->fill([
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => 'testuser',
                'role' => 'user',
                'status' => 'active',
                'verified' => true,
                'email_verified_at' => now(),
                'tips' => [],
                'last_active' => now()->toDateTimeString(),
            ]);
            $user->password = $userPassword;
        } else {
            if (empty($user->status)) {
                $user->status = 'active';
            }
        }
        $user->save();
        $this->bootstrapUser($user);
    }

    private function bootstrapUser(User $user): void
    {
        // Seeder-created users must have the same companion records as users created via CreateUserAction.
        if (! $user->wallet()->exists()) {
            $user->wallet()->create([
                'wallet_number' => 'W-' . strtoupper(\Illuminate\Support\Str::random(16)),
                'balance' => config('wallet.default_balance'),
            ]);
        }

        $user->privacySettings()->firstOrCreate([]);
        $user->permitSettings()->firstOrCreate([]);

        UserNotificationSettings::firstOrCreate([
            'user_id' => $user->id,
            'type' => NotificationType::EMAIL,
        ]);

        UserNotificationSettings::firstOrCreate([
            'user_id' => $user->id,
            'type' => NotificationType::PUSH,
        ]);

        if (! $user->securitySettings()->exists()) {
            $user->securitySettings()->create([
                '2fa' => false,
                '2fa_type' => F2AType::EMAIL,
                'login_notification' => false,
                'login_notification_type' => NotificationType::EMAIL,
            ]);
        }

        if (! $user->businessAccount()->exists()) {
            $user->businessAccount()->create([
                'name' => $user->name,
                'billing_address' => [],
            ]);
        }
    }
}
