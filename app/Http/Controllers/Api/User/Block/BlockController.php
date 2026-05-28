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

namespace App\Http\Controllers\Api\User\Block;

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\People\PeopleCollection;

class BlockController extends Controller
{
    use SupportsApiResponses;

    public function blockUser(Request $request)
    {
        $userId = $request->integer('user_id', 0);

        if ($userId < 1 || $userId === me()->id) {
            return $this->responseValidationError([
                'message' => 'Invalid user.',
                'errors' => [
                    'user_id' => ['Invalid user.']
                ]
            ]);
        }

        $userData = User::activeById($userId)->first();

        if (! $userData) {
            return $this->responseResourceNotFoundError('User', $userId);
        }

        UserBlock::firstOrCreate([
            'blocker_id' => me()->id,
            'blocked_id' => $userId
        ]);

        return $this->responseSuccess([
            'data' => [
                'blocked' => true
            ]
        ]);
    }

    public function unblockUser(Request $request)
    {
        $userId = $request->integer('user_id', 0);

        UserBlock::query()
            ->where('blocker_id', me()->id)
            ->where('blocked_id', $userId)
            ->delete();

        return $this->responseSuccess([
            'data' => [
                'blocked' => false
            ]
        ]);
    }

    public function listBlocks()
    {
        $blockedIds = UserBlock::query()
            ->where('blocker_id', me()->id)
            ->pluck('blocked_id');

        $people = User::active()
            ->whereIn('id', $blockedIds)
            ->latest('id')
            ->take(50)
            ->get();

        return $this->responseSuccess([
            'data' => PeopleCollection::make($people)
        ]);
    }
}
