<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoLike implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;
    public $targetId;

    public function __construct($agent, $targetId)
    {
        $this->agent = $agent;
        $this->targetId = $targetId;
    }

    public function handle()
    {
        $post = Post::find($this->targetId);
        if (!$post) {
            return;
        }

        $reaction = Reaction::query()
            ->where('reactable_type', Post::class)
            ->where('reactable_id', $post->id)
            ->where('unified_id', '1f44d')
            ->first();

        if (!$reaction) {
            $post->reactions()->create([
                'unified_id' => '1f44d',
                'users' => [(int) $this->agent->user_id],
                'reactions_count' => 1,
                'native_symbol' => null,
            ]);
        } else {
            $users = collect($reaction->users ?? [])->map(fn ($id) => (int) $id);
            if ($users->contains((int) $this->agent->user_id)) {
                return;
            }

            $users->push((int) $this->agent->user_id);
            $reaction->users = $users->unique()->values()->all();
            $reaction->reactions_count = count($reaction->users);
            $reaction->save();
        }

        if (method_exists($this->agent, 'logActivity')) {
             $this->agent->logActivity('auto_like', ['post_id' => $post->id]);
        }
    }
}
