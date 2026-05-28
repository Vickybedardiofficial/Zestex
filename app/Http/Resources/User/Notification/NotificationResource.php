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

namespace App\Http\Resources\User\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rawData = is_array($this->data) ? $this->data : [];
        $actor = data_get($rawData, 'actor', []);
        $entity = data_get($rawData, 'entity', []);

        if (! is_array($actor)) {
            $actor = [];
        }

        if (! is_array($entity)) {
            $entity = [];
        }

        $data = [
            'id' => $this->id,
            'message' => $this->safeMessage($rawData),
            'type' => $this->type,
            'actor' => array_merge([
                'id' => null,
                'name' => 'System',
                'avatar_url' => asset(config('user.avatar')),
                'username' => null,
                'type' => 'system',
                'verified' => false,
            ], $actor),
            'entity' => array_merge([
                'id' => null,
                'hash_id' => null,
                'post_hash_id' => null,
                'story_uuid' => null,
                'username' => null,
                'content' => null,
                'preview_lqip_base64' => null,
            ], $entity),
            'is_read' => $this->read_at ? true : false,
            'metadata' => $this->getMetadata(),
            'date' => [
                'time_ago' => $this->created_at->getTimeAgo(),
                'timestamp' => $this->created_at->getTimestamp(),
                'is_today' => $this->created_at->isToday(),
                'is_yesterday' => $this->created_at->isYesterday(),
                'is_this_week' => $this->created_at->isThisWeek(),
                'is_this_month' => $this->created_at->isThisMonth()
            ]
        ];

        return $data;
    }

    private function safeMessage(array $rawData): string
    {
        try {
            return (string) $this->message;
        } catch (\Throwable $th) {
            $group = data_get($rawData, 'message_group', 'general');
            $key = data_get($rawData, 'message_key', 'new_notification');
            $params = data_get($rawData, 'message_params', []);

            if (! is_array($params)) {
                $params = [];
            }

            return (string) __("notifications.{$group}.{$key}", $params);
        }
    }

    private function getMetadata(): array
    {
        if(isset($this->data['metadata']) && is_array($this->data['metadata'])) {
            $metadata = $this->data['metadata'];

            if(isset($metadata['reaction_unified_id'])) {
                $metadata['reaction_image_url'] = reaction_image_url($metadata['reaction_unified_id']);
            }

            return $metadata;
        }

        return [];
    }
}
