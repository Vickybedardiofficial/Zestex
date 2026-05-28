<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BotUserSeeder extends Seeder
{
    public function run(): void
    {
        $botEmail = 'ze@Zestex.test';
        
        $bot = User::firstOrNew(['email' => $botEmail]);
        
        if (! $bot->exists) {
            $bot->fill([
                'first_name' => 'Ze',
                'last_name' => 'Bot',
                'username' => 'ze',
                'role' => 'user',
                'status' => 'active',
                'verified' => true,
                'email_verified_at' => now(),
                'tips' => [],
                'last_active' => now()->toDateTimeString(),
            ]);
            $bot->password = 'ze@123';
        } else {
             if (empty($bot->status)) {
                $bot->status = 'active';
            }
        }
        
        $bot->save();
        
        // Ensure wallet exists
        if (! $bot->wallet()->exists()) {
             $bot->wallet()->create([
                'wallet_number' => 'W-' . strtoupper(Str::random(16)),
                'balance' => 0,
            ]);
        }
        
        // Ensure other settings exist
        $bot->privacySettings()->firstOrCreate([]);
        $bot->permitSettings()->firstOrCreate([]);
    }
}
