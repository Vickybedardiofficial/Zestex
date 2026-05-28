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

namespace App\Http\Resources\Base;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseApiCollection extends ResourceCollection
{
    public function toArray(Request $request):array
    {
        return [
            'status' => 'success',
            'code' => 200,
            'data' => parent::toArray($request)
        ];
    }
}