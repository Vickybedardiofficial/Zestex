<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = config('countries.countries',[]);
echo 'countries_config_count='.count($c).PHP_EOL;
$keys=array_slice(array_keys($c),0,20);
echo 'sample='.implode(',', $keys).PHP_EOL;
