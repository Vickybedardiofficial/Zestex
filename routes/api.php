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

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'app' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);
 
    $user = User::where('email', $request->email)->first();
 
    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }
 
    return $user->createToken($request->device_name)->plainTextToken;
});

Route::prefix('translations')->middleware(['throttle:60,1'])->group(base_path('routes/api/translations.php'));

Route::prefix('public')->middleware(['throttle:60,1'])->group(base_path('routes/api/public.php'));

Route::prefix('bootstrap')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/bootstrap.php'));

Route::prefix('settings')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/account_settings.php'));

Route::prefix('auth')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/auth.php'));

Route::prefix('post/editor')->middleware(['auth:sanctum', 'throttle:240,1'])->group(base_path('routes/api/user/post_editor.php'));

Route::prefix('story/editor')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/story_editor.php'));

Route::prefix('timeline')->middleware(['auth:sanctum', 'throttle:240,1'])->group(base_path('routes/api/user/timeline.php'));

Route::prefix('stories')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/stories.php'));

Route::prefix('profile')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/profile.php'));

Route::prefix('follows')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/follows.php'));

Route::prefix('marketplace')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/marketplace.php'));

Route::prefix('jobs')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/jobs.php'));

Route::prefix('messenger')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/messenger.php'));

Route::prefix('admin')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/admin.php'));

Route::prefix('recommendations')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/recommend.php'));

Route::prefix('explore')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/explore.php'));

Route::prefix('notifications')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/notifications.php'));

Route::prefix('autocompletes')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/autocompletes.php'));

Route::prefix('translator')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/translator.php'));

Route::prefix('feedback')->middleware(['auth:sanctum', 'throttle:15,1'])->group(base_path('routes/api/user/feedback.php'));

Route::prefix('bookmarks')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/bookmarks.php'));

Route::prefix('wallet')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/wallet.php'));

Route::prefix('block')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/block.php'));

Route::prefix('system')->middleware(['throttle:60,1'])->group(base_path('routes/api/system/master.php'));

Route::prefix('ads')->middleware(['throttle:60,1'])->group(base_path('routes/api/ads/ad.php'));

Route::prefix('tips')->middleware(['auth:sanctum', 'throttle:60,1'])->group(base_path('routes/api/user/tips.php'));

Route::prefix('ai')->middleware(['auth:sanctum', 'throttle:120,1'])->group(base_path('routes/api/user/ai.php'));
