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

namespace App\Http\Resources\User\Recommend;

use Illuminate\Http\Request;
use App\Http\Resources\User\Recommend\FollowResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FollowCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->map(function($userItem) {
            return FollowResource::make($userItem->resource);
        })->all();
    }
}
