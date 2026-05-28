<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\Admin\Dash\DashController::class, 'dashboard'])->name('admin.dash.index');

Route::view('/lab', 'admin::lab.index')->name('admin.lab.index');

Route::get('/cache/reset', [App\Http\Controllers\Admin\Cache\CacheController::class, 'reset'])->name('admin.cache.reset');

Route::view('/coming', 'apps.mpa.admin.coming.index')->name('admin.coming.index');

Route::prefix('users')->group(base_path('routes/admin/users/web.php'));

Route::prefix('posts')->group(base_path('routes/admin/posts/web.php'));

Route::prefix('ads')->group(base_path('routes/admin/ads/web.php'));

Route::prefix('stories')->group(base_path('routes/admin/stories/web.php'));

Route::prefix('market')->group(base_path('routes/admin/market/web.php'));

Route::prefix('jobs')->group(base_path('routes/admin/jobs/web.php'));

Route::prefix('config')->group(base_path('routes/admin/config/web.php'));

Route::prefix('payments')->group(base_path('routes/admin/payments/web.php'));
Route::prefix('wallet-transactions')->group(base_path('routes/admin/wallet-transactions/web.php'));

Route::prefix('reports')->group(base_path('routes/admin/reports/web.php'));

Route::prefix('lang')->group(base_path('routes/admin/lang/web.php'));

Route::prefix('currency')->group(base_path('routes/admin/currency/web.php'));

Route::prefix('banning')->group(base_path('routes/admin/banning/web.php'));

Route::prefix('storage')->group(base_path('routes/admin/storage/web.php'));

Route::prefix('authorship')->group(base_path('routes/admin/authorship/web.php'));

Route::prefix('categories')->group(base_path('routes/admin/categories/web.php'));

Route::prefix('pages')->group(base_path('routes/admin/pages/web.php'));

Route::prefix('chats')->group(base_path('routes/admin/chats/web.php'));

Route::prefix('business')->group(base_path('routes/admin/business/web.php'));

Route::prefix('ai-agents')->group(base_path('routes/admin/ai-agents/web.php'));

Route::prefix('ai-analytics')->name('admin.ai-analytics.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
});

Route::prefix('special-events')->name('admin.special-events.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\SpecialEventsController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\SpecialEventsController::class, 'store'])->name('store');
    Route::put('/{id}', [\App\Http\Controllers\Admin\SpecialEventsController::class, 'update'])->name('update');
});
