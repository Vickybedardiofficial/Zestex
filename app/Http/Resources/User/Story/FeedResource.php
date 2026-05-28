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

namespace App\Http\Resources\User\Story;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = ($this->user_id == me()->id);

        return [
            'story_uuid' => $this->story_uuid,
            'relations' => [
                'user' => [
                    'name' => $this->user->name,
                    'avatar_url' => $this->user->avatar_url
                ]
            ],
            'is_seen' => $this->checkIfStorySeen(),
            'is_owner' => $isOwner
        ];
    }

    private function checkIfStorySeen()
    {
        return $this->frames->some(function($frame) {
            return $frame->views->contains('user_id', me()->id);
        });
    }
}
