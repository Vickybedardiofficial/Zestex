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

namespace App\Http\Resources\User\People;

use App\Support\Num;
use Illuminate\Http\Request;
use App\Constants\Relationship;
use Illuminate\Http\Resources\Json\JsonResource;

class PeopleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = (auth_check() && $this->id === me()->id);
        
        return [
            'id' => $this->id,
            'cursor_id' => $this->cursor_id ?? null,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'verified' => $this->isVerified(),
            'username' => $this->username,
            'caption' => $this->getCaption(),
            'website' => $this->website,
            'bio' => $this->bio,
            'followers_count' => [
                'raw' => $this->followers_count,
                'formatted' => Num::abbreviate($this->followers_count)
            ],
            'meta' => [
                'is_owner' => $isOwner,
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
