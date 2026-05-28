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

namespace App\Http\Resources\User\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\User\UserPreviewResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $apiData = [
            'participant_id' => $this->id,
            'joined_at' => [
                'time_ago' => $this->joined_at->getTimeAgo(),
                'raw' => $this->joined_at->getTimestamp(),
            ],
            'meta' => [
                'color' => $this->metadata['color']
            ],
            'relations' => [
                'user' => UserPreviewResource::make($this->user, [
                    'last_active' => [
                        'online' => $this->user->isOnline(),
                        'timestamp' => $this->user->getLastActive()->getTimestamp(),
                        'time_ago' => $this->user->getLastActive()->getTimeAgo(),
                    ]
                ])
            ]
        ];

        if(isset($this->is_group_admin)) {
            $apiData['meta']['is_admin'] = true;
        }

        return $apiData;
    }
}
