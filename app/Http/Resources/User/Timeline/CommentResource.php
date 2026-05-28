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

namespace App\Http\Resources\User\Timeline;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'has_parent' => (empty($this->parent_id)) ? false : true,
            'content' => e($this->content),
            'relations' => [
                'user' => [
                    'avatar_url' => $this->user->avatar_url,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'id' => $this->user->id
                ],
                'reactions' => ReactionCollection::make($this->reactions),
                'parent' => $this->getParentCommentData(),
            ],
            'date' => [
                'iso' => $this->created_at->getIso(),
                'time_ago' => $this->created_at->getTimeAgo()
            ],
            'meta' => [
                'permissions' => [
                    'is_admin' => auth_check() ? me()->isAdmin() : false,
                    'can_edit' => auth_check() ? me()->can('update', $this->resource) : false,
                    'can_delete' => auth_check() ? me()->can('delete', $this->resource) : false
                ],
                'is_translatable' => $this->isContentTranslatable()
            ]
        ];
    }

    private function getParentCommentData()
    {
        if($this->parent) {
            return [
                'content' => Str::limit($this->parent->content, 120),
                'user' => [
                    'name' => $this->parent->user->name,
                    'username' => $this->parent->user->username,
                    'id' => $this->parent->user->id
                ]
            ];
        }
    }
}
