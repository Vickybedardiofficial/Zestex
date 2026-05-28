<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\Admin\Wallet\WalletTransactionController::class, 'index'])
    ->name('admin.wallet-transactions.index');

Route::get('/show/{transactionId}', [App\Http\Controllers\Admin\Wallet\WalletTransactionController::class, 'show'])
    ->name('admin.wallet-transactions.show');

