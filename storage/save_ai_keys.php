<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(App\Http\Controllers\Admin\Config\AiConfigController::class);
$request = Illuminate\Http\Request::create('/admin/config/ai','POST',[
  'pexels_api_key' => 'RxVknOeioHTaWw66q0TDD9bvEtYhLl6PTRurxzLMY1CC312Z7eKRbvAk',
  'unsplash_api_key' => '9poJ2h00S_bRjjodddGX6mTzG39Q6uGRilkfwORAbG8',
  'image_default_provider' => 'pexels',
  'image_fallback_providers' => 'unsplash,pixabay',
  'ai_default_provider' => env('AI_DEFAULT_PROVIDER','xai')
]);
$controller->update($request);

echo "ai_config_save_ok\n";
