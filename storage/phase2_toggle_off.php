<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::table('admin_settings')->updateOrInsert(['key'=>'auto_agent_creation_enabled'],['value'=>'0']);
echo "off\n";
