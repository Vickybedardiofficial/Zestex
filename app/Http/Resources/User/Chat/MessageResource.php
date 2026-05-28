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

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\User\UserPreviewResource;
use App\Http\Resources\User\Morph\LinkSnapshotResource;
use App\Http\Resources\User\Timeline\ReactionCollection;
use App\Http\Resources\User\Chat\MessageAttachmentResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['attachments', 'reactions', 'user', 'participant', 'parent.user', 'parent.participant', 'linkSnapshot']);

        $messageData = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'chat_uuid' => $this->chat_uuid,
            'content' => $this->content,
            'has_parent' => (empty($this->parent_id)) ? false : true,
            'relations' => [
                'user' => UserPreviewResource::make($this->user),
                'reactions' => ReactionCollection::make($this->reactions),
                'attachments' => MessageAttachmentResource::collection($this->attachments),
                'parent' => $this->getParentMessageData(),
                'participant' => [
                    'color' => $this->participant->metadata['color']
                ]
            ],
            'date' => [
                'iso' => $this->created_at->getIso(),
                'time_ago' => $this->created_at->getTimeAgo(),
                'generic' => $this->created_at->getGeneric(),
                'date' => $this->created_at->getDate()
            ],
            'meta' => [
                'is_deleted' => $this->is_deleted,
                'permissions' => [
                    'can_edit' => true,
                    'can_delete' => true
                ],
                'is_translatable' => $this->isMessageTranslatable()
            ]
        ];

        if($this->linkSnapshot) {
            $messageData['relations']['link_snapshot'] = LinkSnapshotResource::make($this->linkSnapshot);
        }

        return $messageData;
    }

    private function getParentMessageData()
    {
        if($this->parent) {
            return [
                'content' => Str::limit($this->parent->content, 120),
                'user' => [
                    'name' => $this->parent->user->name,
                    'username' => $this->parent->user->username,
                    'id' => $this->parent->user->id
                ],
                'participant' => [
                    'color' => $this->parent->participant->metadata['color']
                ]
            ];
        }
    }
}
