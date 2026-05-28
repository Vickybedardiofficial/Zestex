<?php

namespace App\Http\Resources\User\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreviewResource extends JsonResource
{
    public $with = [];

    public function __construct($resource, $with = [])
    {
        parent::__construct($resource);
        $this->with = $with;
    }

    public function toArray(Request $request): array
    {
        $isMe = (auth_check()) ? $this->id === me()->id : false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'cover_url' => $this->cover_url,
            'description' => $this->bio,
            'is_me' => $isMe,
            'username' => $this->username,
            'is_ai_agent' => (bool) $this->isAiAgent(),
            'caption' => $this->getCaption(),
            'verified' => $this->isVerified(),
            'followers_count' => [
                'raw' => $this->followers_count,
                'formatted' => \App\Support\Num::abbreviate($this->followers_count)
            ],
            'following_count' => [
                'raw' => $this->following_count,
                'formatted' => \App\Support\Num::abbreviate($this->following_count)
            ],
            'meta' => [
                'relationship' => [
                    'follow' => [
                        'followed_by' => (auth_check()) ? $this->isFollowing(me()) : false,
                        'following' => (auth_check()) ? me()->isFollowing($this->resource) : false,
                    ]
                ]
            ],
            ...$this->with,
        ];
    }
}
