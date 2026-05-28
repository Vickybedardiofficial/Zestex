<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$groups = DB::table('posts')
  ->select('content', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
  ->where('is_ai_generated',1)
  ->where('status','active')
  ->groupBy('content')
  ->havingRaw('COUNT(*) > 1')
  ->get();

$deleted = 0;
foreach($groups as $g){
  $ids = DB::table('posts')->where('is_ai_generated',1)->where('status','active')->where('content',$g->content)->where('id','!=',$g->keep_id)->pluck('id');
  if($ids->isEmpty()) continue;

  DB::table('posts')->whereIn('quote_post_id',$ids)->update(['quote_post_id'=>null,'is_quoting'=>0,'updated_at'=>now()]);
  DB::table('post_polls')->whereIn('post_id',$ids)->delete();
  DB::table('comments')->whereIn('post_id',$ids)->delete();
  DB::table('posts')->whereIn('id',$ids)->delete();
  $deleted += $ids->count();
}

echo 'dedupe_groups=' . count($groups) . PHP_EOL;
echo 'dedupe_deleted_posts=' . $deleted . PHP_EOL;
