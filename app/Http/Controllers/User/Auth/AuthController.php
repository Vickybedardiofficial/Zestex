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

namespace App\Http\Controllers\User\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\EmailConfirmation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Actions\User\CreateUserAction;
use Illuminate\Support\Facades\Validator;
use App\Events\User\Auth\UserLoggedInEvent;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth::index');
    }

    public function signup()
    {
        return view('auth::signup');
    }

    public function forgotPassword()
    {
        return view('auth::forgot');
    }

    public function resetPassword(string $token)
    {
        $confirmationData = $this->getTokenData($token);

        return view('auth::reset', [
            'confirmationData' => $confirmationData
        ]);
    }

    public function forgotSuccess(string $hash_id)
    {
        $confirmationData = $this->getTokenDataByHashId($hash_id);

        return view('auth::forgot-success', [
            'confirmationData' => $confirmationData
        ]);
    }

    public function signupSuccess(string $hashId)
    {
        $confirmationData = $this->getTokenDataByHashId($hashId);

        return view('auth::signup-success', [
            'confirmationData' => $confirmationData
        ]);
    }

    public function confirmSignup(string $token)
    {
        $confirmationData = $this->getTokenData($token);
        
        $user = User::where('email', $confirmationData->email)->first();

        if ($user) {
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            Auth::guard('web')->login($user, true);

            event(new UserLoggedInEvent(me()));

            $confirmationData->delete();

            return redirect()->route('user.onboarding.index', 'one');
        }

        // Fallback if user was not found
        $confirmationData->delete();
        return redirect()->route('user.auth.signup')->withErrors(['emailAddress' => 'Account not found. Please sign up again.']);
    }

    private function getTokenDataByHashId(string $hash_id)
    {
        return EmailConfirmation::whereHashId($hash_id)->firstOrFail();
    }

    private function getTokenData($token)
    {
        $validator = Validator::make([
            'token' => $token
        ], [
            'token' => ['required', 'string', 'uuid']
        ]);

        if($validator->fails()) {
            abort(404);
        }

        return EmailConfirmation::where('token', $token)->firstOrFail();
    }
}
