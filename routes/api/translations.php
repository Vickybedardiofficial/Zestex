<?php

use Illuminate\Support\Facades\Route;

Route::get('/app', function () {
	$locale = request()->get('locale', 'en');
	$catalog = app(\App\Support\ApiTranslationCatalog::class)->get($locale);

    return response()->json([
		'data' => $catalog,
	]);
});
