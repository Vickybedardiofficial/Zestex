<?php
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Post;
use App\Http\Resources\User\Timeline\TimelineResource;

$p = Post::query()->with(['user','reactions','comments.user','quotedPost','linkSnapshot'])->latest('id')->first();
$data = TimelineResource::make($p)->resolve();
echo json_encode([
  'post_id' => $data['id'] ?? null,
  'username' => $data['relations']['user']['username'] ?? null,
  'user_is_ai_agent' => $data['relations']['user']['is_ai_agent'] ?? null,
  'meta_is_agent_post' => $data['meta']['is_agent_post'] ?? null,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
