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

namespace App\Http\Controllers\Api\User\Recommend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Actions\Recommend\FetchFollowRecommendation;
use App\Http\Resources\User\Recommend\FollowCollection;

class FollowRecommendController extends Controller
{
    use SupportsApiResponses;

    public function getFollowRecommendations(Request $request)
    {
        $limit = $request->integer('limit', config('recommend.follow_recommendation_limit'));

        $recommendations = (new FetchFollowRecommendation())->handle($limit);

        return $this->responseSuccess([
            'data' => FollowCollection::make($recommendations)
        ]);
    }
}
