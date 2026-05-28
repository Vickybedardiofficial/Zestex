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

namespace App\Http\Controllers\Api\Admin;

use Throwable;
use App\Models\User;
use App\Enums\BlacklistType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Actions\User\DeleteUserAction;
use App\Services\Blacklist\BlacklistService;
use App\Traits\Http\Api\SupportsApiResponses;

class AdminController extends Controller
{
    use SupportsApiResponses;

    private BlacklistService $blacklistService;

    public function __construct(BlacklistService $blacklistService) {
        $this->blacklistService = $blacklistService;
    }

    public function deleteProfile(Request $request)
    {
        // TODO
        // Move admin role check to middleware later.

        $userId = $request->integer('user_id');

        if(me()->isAdmin()) {

            $deletedUser = User::find($userId);

            try {
                $this->blacklistService->setType(BlacklistType::IP)->add($deletedUser->ip_address);
                $this->blacklistService->setType(BlacklistType::EMAIL)->add($deletedUser->email);
            } catch (Throwable $th) {
                Log::error($th->getMessage());
            }

            (new DeleteUserAction($deletedUser))->execute();

            return $this->responseSuccess([
                'data' => null
            ]);
        }

        else {
            return $this->responseResourceNotFoundError('User', $userId);
        }
    }
}
