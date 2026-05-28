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

namespace App\Http\Resources\User\Profile;

use Carbon\Carbon;
use App\Support\Num;
use Illuminate\Http\Request;
use App\Constants\Relationship;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $auth = auth_check();
        $meUser = $auth ? me() : null;
        $isMe = $auth && $meUser ? ($this->id == $meUser->id) : false;

        $businessAccount = $this->businessAccount;
        $showBusiness = false;

        if ($businessAccount && ! empty($businessAccount->name)) {
            $showBusiness = $isMe || (! empty($businessAccount->is_reviewed) && ! empty($businessAccount->verified));
        }

        $profileData = [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'caption' => $this->getCaption(),
            'avatar_url' => $this->avatar_url,
            'cover_url' => $this->cover_url,
            'profile_url' => $this->profile_url,
            'category' => $this->category,
            'bio' => $this->bio,
            'join_date' => [
                'raw' => $this->getCreatedAt()->getTimestamp(),
                'formatted' => $this->getCreatedAt()->getCalendar()
            ],
            'gender' => $this->gender,
            'website' => $this->website,
            'verified' => $this->verified,
            'publications_count' => [
                'raw' => (int) ($this->publications_count ?? 0),
                'formatted' => Num::abbreviate((int) ($this->publications_count ?? 0))
            ],
            'followers_count' => [
                'raw' => (int) ($this->followers_count ?? 0),
                'formatted' => Num::abbreviate((int) ($this->followers_count ?? 0))
            ],
            'following_count' => [
                'raw' => (int) ($this->following_count ?? 0),
                'formatted' => Num::abbreviate((int) ($this->following_count ?? 0))
            ],
            'business_account' => $showBusiness ? [
                'name' => $businessAccount->name,
                'verified' => (bool) $businessAccount->verified,
                'is_reviewed' => (bool) $businessAccount->is_reviewed,
            ] : null,
            'meta' => [
                'is_owner' => $isMe,
                'permissions' => [
                    'can_sanction' => ($auth && ! $isMe && $meUser?->isAdmin()),
                    'can_follow' => ($auth && $meUser) ? ($this->canFollow($meUser) && ! $meUser->isBlockedWith($this->resource)) : false,
                    'can_mention' => (! $isMe),
                    'can_message' => ($auth && ! $isMe && $meUser && ! $meUser->isBlockedWith($this->resource)),
                    'can_block' => ($auth && ! $isMe),
                    'can_report' => ($auth && ! $isMe),
                    'can_mute' => ($auth && ! $isMe),
                ],
                'relationship' => [
                    Relationship::FOLLOW_GROUP => [
                        Relationship::FOLLOWING => ($auth && $meUser) ? $meUser->isFollowing($this->resource) : false,
                        Relationship::FOLLOWED_BY => ($auth && $meUser) ? $this->isFollowing($meUser) : false,
                        Relationship::REQUESTED_BY => false,
                        Relationship::REQUESTED => ($auth && $meUser) ? $meUser->followRequested($this->resource) : false
                    ],
                    Relationship::BLOCK_GROUP => [
                        Relationship::BLOCKING => ($auth && $meUser) ? $meUser->isBlocking($this->resource) : false,
                        Relationship::BLOCKED_BY => ($auth && $meUser) ? $meUser->isBlockedBy($this->resource) : false
                    ],
                    Relationship::MUTING_GROUP => [
                        Relationship::MUTING => false,
                        Relationship::MUTING_NOTIFICATIONS => false
                    ]
                ]
            ]
        ];

        if(empty($this->privacySettings->country_privacy)) {
            $profileData['country'] = $this->country;
            $profileData['country_name'] = $this->country_name;
        }

        if(empty($this->privacySettings->city_privacy)) {
            $profileData['city'] = $this->city;
        }

        return $profileData;
    }
}
