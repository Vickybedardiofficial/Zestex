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

namespace App\Http\Controllers\User\Auth\Social;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Services\Auth\Social\SocialAuthService;
use Laravel\Socialite\Two\FacebookProvider;

class FacebookAuthController extends Controller
{
    protected $defaultScopes = ['email', 'public_profile'];

    protected array $driverCredentials;

    protected string $driverName = 'facebook';

    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
        $this->driverCredentials = $this->socialAuthService->getCredentials($this->driverName);
    }

    public function index()
    {
        $socialite = Socialite::buildProvider(FacebookProvider::class, $this->driverCredentials);

        return $socialite->scopes($this->defaultScopes)->redirect();
    }

    public function callbackHandler()
    {
        $socialiteUser = $this->fetchUserData();
    }

    private function fetchUserData()
    {
        return Socialite::buildProvider(FacebookProvider::class, $this->driverCredentials)->user();
    }
}
