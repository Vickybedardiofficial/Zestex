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

namespace App\Http\Controllers\Api\User\Bootstrap;

use App\Info\Zestex;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;

class BootstrapController extends Controller
{
    use SupportsApiResponses;

    public function bootstrap()
    {
        return $this->responseSuccess([
            'data' => [
                'version' => Zestex::VERSION,
                'name' => config('app.name'),
                'author' => [
                    'name' => 'Vicky Bedardi Yadav. Full-Stack Web Developer.',
                    'email' => 'vicktbedardi9@gmail.com'
                ],
                'auth' => [
                    'status' => auth_check(),
                    'user' => $this->getUserData()
                ]
            ]
        ]);
    }

    private function getUserData()
    {
        if(auth_check()) {
            $me = me();

            $userData = [
                'id' => $me->id,
                'name' => $me->name,
                'avatar_url' => $me->avatar_url,
                'cover_url' => $me->cover_url,
                'first_name' => $me->first_name,
                'last_name' => $me->last_name,
                'caption' => $me->getCaption(),
                'username' => $me->username,
                'has_tips' => $me->has_tips,
                'tips' => $me->tips,
                'is_master_account' => $me->isMasterAccount(),
                'is_author' => $me->isAuthor(),
                'verification' => [
                    'status' => $me->verified,
                    'date' => $me->verified_at ? $me->verified_at->getIso() : null
                ],
                'meta' => [
                    'is_admin' => $me->isAdmin()
                ]
            ];

            if($me->isAdmin()) {
                $userData['meta']['admin'] = [
                    'url' => route('admin.dash.index'),
                ];
            }
            
            return $userData;   
        }
        
        return null;
    }
}
