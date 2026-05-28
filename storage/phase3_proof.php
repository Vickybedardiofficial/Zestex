<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$since = now()->subMinutes(30);
$recentComments = App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))
  ->where('created_at','>=',$since)
  ->count();

echo 'recent_ai_comments_30m='.$recentComments.PHP_EOL;

$logs = App\Models\AiAgentActivityLog::where('action_type','comment_created')
  ->where('created_at','>=',$since)
  ->get();
$chain = 0;
foreach($logs as $l){
  $src = is_array($l->action_data ?? null) ? ($l->action_data['source'] ?? null) : null;
  if($src === 'chain_reaction'){ $chain++; }
}
echo 'recent_chain_reaction_logs_30m='.$chain.PHP_EOL;
