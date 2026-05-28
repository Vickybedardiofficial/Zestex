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

namespace App\Http\Controllers\User\Onboarding;

use App\Http\Controllers\Controller;

class OnboardingController extends Controller
{
    public function index($step)
    {
        $stepMap = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];

        return view('onboarding::index', [
            'step' => $step,
            'stepNumber' => (isset($stepMap[$step]) ? $stepMap[$step] : 1),
            'totalSteps' => count($stepMap)
        ]);
    }
}
