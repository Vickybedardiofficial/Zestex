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

namespace App\Http\Resources\User\Recommend;

use Illuminate\Http\Request;
use App\Constants\Relationship;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'username' => $this->username,
            'caption' => $this->getCaption(),
            'verified' => $this->isVerified(),
            'meta' => [
                'relationship' => [
                    Relationship::FOLLOW_GROUP => [
                        Relationship::FOLLOWING => me()->isFollowing($this->resource),
                        Relationship::FOLLOWED_BY => $this->isFollowing(me()),
                        Relationship::REQUESTED_BY => false,
                        Relationship::REQUESTED => false
                    ]
                ]
            ]
        ];
    }
}
