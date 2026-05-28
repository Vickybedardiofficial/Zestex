<?php

use Illuminate\Support\Facades\Route;

Route::name('admin.ai-agents.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'index'])->name('index');
    Route::get('/analytics', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'analytics'])->name('analytics'); // Part 9
    Route::post('/toggle-auto-creation', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'toggleAutoCreation'])->name('toggle-auto-creation');
    Route::post('/toggle-engagement', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'toggleEngagement'])->name('toggle-engagement');
    Route::middleware('sided.layout')->get('/create', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'store'])->name('store');
    Route::middleware('sided.layout')->get('/{id}', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'show'])->name('show');
    Route::middleware('sided.layout')->get('/{id}/edit', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle', [App\Http\Controllers\Admin\AiAgent\AiAgentController::class, 'toggleStatus'])->name('toggle');
});
