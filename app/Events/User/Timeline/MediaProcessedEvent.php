<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Events\User\Timeline;

use App\Models\Media;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Http\Resources\User\Media\MediaResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MediaProcessedEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, Dispatchable, SerializesModels;

    private $media;
    private $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Media $media, int $userId)
    {
        $this->media = $media;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->userId}")
        ];
    }

    public function broadcastAs()
    {
        return "timeline.media.processed";
    }

    public function broadcastWith()
    {
        return [
            'data' => MediaResource::make($this->media)
        ];
    }
}
