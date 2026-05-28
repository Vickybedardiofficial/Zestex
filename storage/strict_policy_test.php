<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$human = App\Models\User::where('type','!=','ai_agent')->first();
$aiUser = App\Models\User::where('type','ai_agent')->first();
$aiPost = App\Models\Post::where('user_id',$aiUser->id)->latest('id')->first();
$humanPost = App\Models\Post::where('user_id',$human->id)->latest('id')->first();

echo 'human_id='.$human->id.' ai_user_id='.$aiUser->id.PHP_EOL;
echo 'human_can_follow_ai=' . ($human->canFollow($aiUser) ? 'yes':'no') . PHP_EOL;
echo 'ai_can_follow_human=' . ($aiUser->canFollow($human) ? 'yes':'no') . PHP_EOL;

try {
    $human->follow($aiUser);
    echo "human_follow_ai=unexpected_success\n";
} catch (Throwable $e) {
    echo 'human_follow_ai=blocked:'.$e->getMessage().PHP_EOL;
}

try {
    $aiUser->follow($human);
    echo "ai_follow_human=unexpected_success\n";
} catch (Throwable $e) {
    echo 'ai_follow_human=blocked:'.$e->getMessage().PHP_EOL;
}

$aiAgent = App\Models\AiAgent::where('user_id',$aiUser->id)->first();

try {
    App\Models\Comment::create([
        'user_id'=>$aiUser->id,
        'post_id'=>$humanPost->id,
        'content'=>'AI to human should block '.time(),
    ]);
    echo "ai_comment_human=unexpected_success\n";
} catch (Throwable $e) {
    echo 'ai_comment_human=blocked:'.$e->getMessage().PHP_EOL;
}

try {
    App\Models\Comment::create([
        'user_id'=>$human->id,
        'post_id'=>$aiPost->id,
        'content'=>'human to ai should block '.time(),
    ]);
    echo "human_comment_ai=unexpected_success\n";
} catch (Throwable $e) {
    echo 'human_comment_ai=blocked:'.$e->getMessage().PHP_EOL;
}
