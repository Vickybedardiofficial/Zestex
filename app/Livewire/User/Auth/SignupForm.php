<?php

namespace App\Livewire\User\Auth;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\EmailConfirmation;
use App\Support\SocialLoginDrivers;
use App\Services\Blacklist\BlacklistService;
use App\Models\User;
use App\Models\AdminSetting;
use App\Enums\User\UserStatus;
use App\Actions\User\CreateUserAction;
use Illuminate\Support\Facades\Auth;
use App\Events\User\Auth\UserLoggedInEvent;
use App\Events\User\Auth\UserRegisteredEvent;

class SignupForm extends Component
{
    public string $emailAddress;
    public string $password = '';
    public $activeSocialDrivers;
    public $showAllSocialOptions = false;

    public function mount(SocialLoginDrivers $socialLoginDrivers)
    {
        $this->activeSocialDrivers = $socialLoginDrivers->getActiveDrivers();
    }

    public function showAllSocialLoginOptions()
    {
        $this->showAllSocialOptions = true;
    }

    public function render()
    {
        return view('livewire.user.auth.signup-form');
    }

    public function submitForm()
    {
        $this->validate(rules: [
            'emailAddress' => ['required', 'string', 'email', 'max:62', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:62']
        ], attributes: [
            'emailAddress' => __('auth.email'),
            'password' => __('auth.password_label')
        ]);

        if($this->checkIfEmailBlacklisted()) {
            $this->addError('emailAddress', __('auth.email_blocked'));

            return false;
        }

        $requireVerification = AdminSetting::where('key', 'user_email_verification')->value('value');
        $requireVerification = $requireVerification === null ? true : filter_var($requireVerification, FILTER_VALIDATE_BOOLEAN);

        // Create User Immediately
        $userData = (new CreateUserAction([
            'email' => $this->emailAddress,
            'password' => $this->password,
        ]))->execute();

        if (!$requireVerification) {
            // Verification OFF: Verify and Login
            $userData->update([
                'email_verified_at' => now(),
                'status' => UserStatus::ONBOARDING // Ensure they go to onboarding
            ]);

            Auth::login($userData, true);

            event(new UserLoggedInEvent(me()));

            return redirect()->route('user.onboarding.index', 'one');
        } else {
            // Verification ON: Standard Flow (User created but unverified)
            $emailToken = Str::uuid();

            $emailConfirmation = EmailConfirmation::create([
                'email' => $this->emailAddress,
                'token' => $emailToken
            ]);

            event(new UserRegisteredEvent([
                'email' => $this->emailAddress,
                'link' => route('user.auth.confirm-signup', ['token' => $emailToken])
            ]));

            $this->redirect(route('user.auth.signup-success', ['hashId' => $emailConfirmation->hash_id]));
        }
    }

    private function checkIfEmailBlacklisted()
    {
        $blacklistService = app(BlacklistService::class);
        
        return $blacklistService->isEmailBlacklisted($this->emailAddress);
    }
}
