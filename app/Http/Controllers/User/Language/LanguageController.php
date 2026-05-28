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

namespace App\Http\Controllers\User\Language;

use App\Http\Controllers\Controller;
use App\Support\Languages;

class LanguageController extends Controller
{
    private $appLanguages;

    public function __construct(Languages $appLanguages) {
        $this->appLanguages = $appLanguages;
    }

    public function switchLanguage(string $lang)
    {
        $this->appLanguages->switchLanguage($lang);
        
        return redirect()->back();
    }
}
