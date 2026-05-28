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

namespace App\Http\Controllers\Api\Public\Bootstrap;

use App\Info\Zestex;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;

class PublicBootstrapController extends Controller
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
                    'status' => false,
                    'user' => null
                ]
            ]
        ]);
    }
}
