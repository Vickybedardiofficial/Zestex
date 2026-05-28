<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug/logo', function () {
    $theme = theme_name();
    $logoUrl = (theme_name() === 'dark') ? 'dark.png' : 'light.png';
    $fullUrl = asset("assets/logos/{$logoUrl}");
    
    return "Theme: {$theme}<br>Logo File: {$logoUrl}<br>Full URL: {$fullUrl}<br><img src='{$fullUrl}' alt='Logo Test' style='background: #ccc; padding: 20px;'>";
});
