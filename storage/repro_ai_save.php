<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try{
  $controller = app(App\Http\Controllers\Admin\Config\AiConfigController::class);
  $request = Illuminate\Http\Request::create('/admin/config/ai','POST',[
    'pexels_api_key' => 'TEST_PEXELS_KEY_123',
    'unsplash_api_key' => 'TEST_UNSPLASH_KEY_456',
    'ai_default_provider' => 'xai',
    'image_default_provider' => 'pexels'
  ]);
  $response = $controller->update($request);
  echo 'ok_response='.get_class($response).PHP_EOL;
}catch(Throwable $e){
  echo 'error='.$e->getMessage().PHP_EOL;
  echo $e->getTraceAsString().PHP_EOL;
}
