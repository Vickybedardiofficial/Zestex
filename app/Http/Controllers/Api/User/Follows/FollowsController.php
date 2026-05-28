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

namespace App\Http\Controllers\Api\User\Follows;

use App\Models\User;
use Illuminate\Http\Request;
use App\Constants\Relationship;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Notifications\User\Follows\NewFollowerNotification;
use App\Notifications\User\Follows\FollowAcceptNotification;
use App\Notifications\User\Follows\FollowRequestNotification;

class FollowsController extends Controller
{
    use SupportsApiResponses;

    public function followUser(Request $request)
    {
        $userId = $request->integer('id', 0);

        $userData = User::activeById($userId)->first();
        
        if($userData) {
            if (me()->isBlockedWith($userData)) {
                return $this->responseError([
                    'message' => 'Follow is not allowed for this user.',
                    'errors' => [
                        'id' => ['Follow is not allowed for this user.']
                    ]
                ], 403);
            }

            if(me()->isFollowing($userData) || me()->followRequested($userData)) {
                me()->unFollow($userData);
            }
            else {
                if(me()->canFollow($userData)) {
                    $follow = me()->follow($userData);

                    if($follow->status->isRequested()) {
                        $userData->notifyNow(new FollowRequestNotification());
                    }
                    else if($follow->status->isFollowing()) {
                        $userData->notifyNow(new NewFollowerNotification());
                    }
                }
                else {
                    return $this->responseError([
                        'message' => 'Follow is not allowed for this account type.',
                        'errors' => [
                            'id' => ['Follow is not allowed for this account type.']
                        ]
                    ]);
                }
            }

            return $this->responseSuccess([
                'data' => [
                    'relationship' => [
                        Relationship::FOLLOW_GROUP => [
                            Relationship::FOLLOWING => me()->isFollowing($userData),
                            Relationship::REQUESTED => me()->followRequested($userData)
                        ]
                    ]
                ]
            ]);
        }

        return $this->responseResourceNotFoundError('User', $userId);
    }

    public function acceptFollowRequest(Request $request)
    {
        $userId = $request->integer('id', 0);

        $userData = User::activeById($userId)->first();

        if($userData) {
            $follow = me()->acceptFollowRequest($userData);

            if($follow && $follow->status->isFollowing()) {
                $userData->notifyNow(new FollowAcceptNotification());
            }

            return $this->responseSuccess([
                'data' => null
            ]);
        }

        return $this->responseResourceNotFoundError('User', $userId);
    }
}
