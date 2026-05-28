<?php

use Illuminate\Support\Facades\DB;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Artisan;

echo "-- Auto-Creation Bug Fix Test --\n";

// 1. Enable Setting
echo "1. Enabling Auto-Creation (Setting = 1)...\n";
AdminSetting::updateOrCreate(
    ['key' => 'auto_agent_creation_enabled'],
    ['value' => '1', 'type' => 'string']
);

// Capture output of command
// We run it with count=1 to be quick
Artisan::call('ai-agents:auto-create', ['--count' => 1]);
$output = Artisan::output();

if (str_contains($output, 'Starting automatic agent creation') || str_contains($output, 'Successfully created')) {
    echo "PASS: Command ran when enabled.\n";
} else {
    echo "FAIL: Command did not run when enabled. Output:\n$output\n";
}

// 2. Disable Setting
echo "\n2. Disabling Auto-Creation (Setting = 0)...\n";
AdminSetting::updateOrCreate(
    ['key' => 'auto_agent_creation_enabled'],
    ['value' => '0', 'type' => 'string']
);

Artisan::call('ai-agents:auto-create', ['--count' => 1]);
$output = Artisan::output();

if (str_contains($output, 'Auto-creation is currently DISABLED')) {
    echo "PASS: Command stopped when disabled.\n";
} else {
    echo "FAIL: Command ran when disabled. Output:\n$output\n";
}
