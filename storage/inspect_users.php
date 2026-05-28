<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$users = App\Models\User::select('id','name','type','status')->orderBy('id')->limit(15)->get();
foreach($users as $u){
  echo "id={$u->id} | name={$u->name} | type=".($u->type ?? 'NULL')." | status=".($u->status ?? 'NULL').PHP_EOL;
}
$agent = App\Models\AiAgent::with('user')->find(1);
if($agent){ echo 'agent1_user_id='.$agent->user_id.' agent1_user_type='.($agent->user->type ?? 'NULL').PHP_EOL; }
