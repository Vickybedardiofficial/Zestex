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

namespace App\Http\Controllers\User\Theme;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    public function switchTheme(string $theme)
    {
        if(in_array($theme, ['dark', 'light'])) {
            if(auth_check()) {
                me()->update([
                    'theme' => $theme
                ]);
            }
            
            Cookie::queue('theme', $theme, 60 * 24 * 365 * 3);
        
            session()->put('theme', $theme);
        }
        
        return redirect()->back();
    }
}
