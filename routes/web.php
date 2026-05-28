<?php
/*
|--------------------------------------------------------------------------
| Zestex - The Ultimate Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav. Full-Stack Web Developer, UI/UX Designer.
| Website: 
| E-mail: vicktbedardi9@gmail.com
| Instagram: 
| Telegram: 
|--------------------------------------------------------------------------
| Copyright (c)  Zestex. All rights reserved.
|--------------------------------------------------------------------------
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\PublicSeoController;

Route::get('/fix-agent-schema', function () {
    try {
        $files = [
            base_path('database/migrations/2026_02_16_000001_create_ai_agents_table.php'),
            base_path('database/migrations/2026_02_15_183516_create_ai_agents_table.php'),
        ];
        $output = '';
        foreach ($files as $file) {
            if (file_exists($file)) {
                if (rename($file, $file . '.bak')) {
                    $output .= "Renamed " . basename($file) . " to .bak\n";
                } else {
                    $output .= "Failed to rename " . basename($file) . "\n";
                }
            } else {
                $output .= basename($file) . " not found (maybe already renamed via shell?)\n";
            }
        }
        
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output .= "\nMigration run successfully. Output: " . \Illuminate\Support\Facades\Artisan::output();
        
        return nl2br($output);
    } catch (\Throwable $e) {
        return 'Output so far: ' . nl2br($output) . '<br>Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
});

Route::name('user.')->group(function() {
    Route::get('/switch-language/{lang}', [App\Http\Controllers\User\Language\LanguageController::class, 'switchLanguage'])->name('language.switch');
    Route::get('/switch-theme/{theme}', [App\Http\Controllers\User\Theme\ThemeController::class, 'switchTheme'])->name('theme.switch');
});

Route::name('user.')->prefix('auth')->middleware(['guest'])->group(function() {
    Route::get('/login', [App\Http\Controllers\User\Auth\AuthController::class, 'index'])->name('auth.index');
    Route::get('/signup', [App\Http\Controllers\User\Auth\AuthController::class, 'signup'])->name('auth.signup');
    Route::get('/forgot-password', [App\Http\Controllers\User\Auth\AuthController::class, 'forgotPassword'])->name('auth.forgot');
    Route::get('/reset-password/{token}', [App\Http\Controllers\User\Auth\AuthController::class, 'resetPassword'])->name('auth.reset');
    Route::get('/confirm-signup/{token}', [App\Http\Controllers\User\Auth\AuthController::class, 'confirmSignup'])->name('auth.confirm-signup');
    Route::get('/forgot-success/{hashId}', [App\Http\Controllers\User\Auth\AuthController::class, 'forgotSuccess'])->name('auth.forgot-success');
    Route::get('/signup-success/{hashId}', [App\Http\Controllers\User\Auth\AuthController::class, 'signupSuccess'])->name('auth.signup-success');
});

Route::name('user.')->prefix('auth')->middleware(['auth'])->group(function() {
    Route::get('/link-account', [App\Http\Controllers\User\Auth\LinkerController::class, 'index'])->name('linker.index');
});

Route::name('user.')->prefix('onboarding')->middleware(['auth'])->group(function() {
    Route::get('/step-{step}', [App\Http\Controllers\User\Onboarding\OnboardingController::class, 'index'])->whereIn('step', ['one', 'two', 'three', 'four'])->name('onboarding.index');
});

Route::prefix('switcher')->get('/device/{type}', function ($type) {
    Cookie::queue('device_type', $type);

    return redirect()->back();
})->name('device.switch')->whereIn('type', ['desktop', 'mobile']);

// Handle bare publication path gracefully. A valid publication URL requires a hash id.
Route::get('/publication', function () {
    return redirect('/');
});

// Public SEO endpoints for crawlers and social bots.
Route::get('/publication/{hashId}', [PublicSeoController::class, 'publication'])
    ->where('hashId', '[A-Za-z0-9]+')
    ->name('public.publication');
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('public.sitemap');

// Guest-accessible profile pages (SPA shell).
Route::get('/@{username}/{tab?}', function (Request $request) {
    $deviceType = Cookie::get('device_type', 'desktop');

    if ($deviceType == 'mobile') {
        return view('mobile::index');
    }

    return view('desktop::index');
})->where([
    'username' => '[A-Za-z0-9_.]+',
    'tab' => 'posts|media|info'
])->name('public.profile');

require __DIR__ . '/debug_logo.php';

Route::middleware(['user.status', 'auth:sanctum'])->group(function() {
    Route::get('/', function () {
        $deviceType = Cookie::get('device_type', 'desktop');
        
        if($deviceType === 'mobile') {
            return view('mobile::index');
        }

        return view('desktop::index');
    })->name('user.desktop.index');

    Route::get('{any}', function (Request $request) {
        $deviceType = Cookie::get('device_type', 'desktop');
        
        if($deviceType === 'mobile') {
            return view('mobile::index');
        }

        return view('desktop::index');
    })->where('any', '^(?!api(?:/|$)).*');
});

// Fallback route for guests - serve SPA shell
Route::get('{any}', function (Request $request) {
    $deviceType = Cookie::get('device_type', 'desktop');
    
    if($deviceType === 'mobile') {
        return view('mobile::index');
    }

    return view('desktop::index');
})->where('any', '^(?!api(?:/|$)).*');
