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

namespace App\Http\Controllers\Api\User\Settings;

use App\Support\Languages;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Language\LanguageResource;

class LanguageSettingsController extends Controller
{
    use SupportsApiResponses;

    public $availableLanguages;

    public function __construct(Languages $availableLanguages) {
        $this->availableLanguages = $availableLanguages;
    }

    public function getLanguages()
    {
        return $this->responseSuccess([
            'data' => $this->availableLanguages->getLanguages()->map(function($localeItem) {
                return LanguageResource::make($localeItem);
            })
        ]);
    }

    public function switchLanguage(Request $request)
    {
        $lang = $request->input('language', 'en');

        $this->availableLanguages->switchLanguage($lang);

        return $this->responseSuccess([
            'data' => null
        ]);
    }
}
