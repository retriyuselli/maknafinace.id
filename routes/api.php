<?php

use App\Http\Controllers\Api\ItemPurchaseCodeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:item-purchase-code')->group(function (): void {
    Route::post('/item-purchase-codes/verify', [ItemPurchaseCodeController::class, 'verify'])
        ->name('api.item-purchase-codes.verify');
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::get('/me', [MeController::class, 'show'])->name('api.v1.me');
        Route::patch('/me', [MeController::class, 'update'])->name('api.v1.me.update');
        Route::post('/me/avatar', [MeController::class, 'updateAvatar'])->name('api.v1.me.avatar');
        Route::put('/me/password', [MeController::class, 'updatePassword'])
            ->middleware('throttle:5,1')
            ->name('api.v1.me.password');
        Route::get('/me/devices', [MeController::class, 'devices'])->name('api.v1.me.devices');

        Route::get('/finance/dashboard', [FinanceController::class, 'dashboard'])
            ->name('api.v1.finance.dashboard');
        Route::get('/finance/projects', [FinanceController::class, 'projects'])
            ->name('api.v1.finance.projects');
        Route::get('/finance/prospects', [FinanceController::class, 'prospects'])
            ->name('api.v1.finance.prospects');
    });
});
