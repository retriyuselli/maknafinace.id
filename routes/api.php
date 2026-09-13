<?php

use App\Http\Controllers\Api\ItemPurchaseCodeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MobileModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:item-purchase-code')->group(function (): void {
    Route::post('/item-purchase-codes/verify', [ItemPurchaseCodeController::class, 'verify'])
        ->name('api.item-purchase-codes.verify');
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');
    Route::post('/auth/google', [AuthController::class, 'google'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.google');

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
        Route::get('/me/compensation', [MeController::class, 'compensation'])
            ->name('api.v1.me.compensation');

        Route::prefix('modules')->group(function () {
            Route::get('/', [MobileModuleController::class, 'index'])
                ->name('api.v1.modules.index');
            Route::get('/{key}/form', [MobileModuleController::class, 'form'])
                ->name('api.v1.modules.form');
            Route::get('/{key}', [MobileModuleController::class, 'show'])
                ->name('api.v1.modules.show');
            Route::post('/{key}', [MobileModuleController::class, 'store'])
                ->name('api.v1.modules.store');
            Route::get('/{key}/{id}', [MobileModuleController::class, 'detail'])
                ->whereNumber('id')
                ->name('api.v1.modules.detail');
            Route::get('/{key}/{id}/draft-kontrak', [MobileModuleController::class, 'draftKontrak'])
                ->whereNumber('id')
                ->name('api.v1.modules.draft-kontrak');
            Route::get('/{key}/{id}/pdf', [MobileModuleController::class, 'pdf'])
                ->whereNumber('id')
                ->name('api.v1.modules.pdf');
            Route::patch('/{key}/{id}', [MobileModuleController::class, 'update'])
                ->whereNumber('id')
                ->name('api.v1.modules.update');
        });

        Route::get('/finance/dashboard', [FinanceController::class, 'dashboard'])
            ->name('api.v1.finance.dashboard');
        Route::get('/finance/transactions', [FinanceController::class, 'transactions'])
            ->name('api.v1.finance.transactions');
        Route::get('/finance/payments/{id}/proof', [FinanceController::class, 'paymentProof'])
            ->whereNumber('id')
            ->name('api.v1.finance.payments.proof');
        Route::get('/finance/reports/summary', [FinanceController::class, 'reportSummary'])
            ->name('api.v1.finance.reports.summary');
        Route::get('/finance/reports/pdf', [FinanceController::class, 'reportPdf'])
            ->name('api.v1.finance.reports.pdf');
        Route::get('/finance/reports/excel', [FinanceController::class, 'reportExcel'])
            ->name('api.v1.finance.reports.excel');
        Route::get('/finance/piutangs', [FinanceController::class, 'piutangs'])
            ->name('api.v1.finance.piutangs');
        Route::get('/finance/piutangs/{id}', [FinanceController::class, 'piutangShow'])
            ->whereNumber('id')
            ->name('api.v1.finance.piutangs.show');
        Route::get('/finance/prospects', [FinanceController::class, 'prospects'])
            ->name('api.v1.finance.prospects');
        Route::post('/finance/prospects', [FinanceController::class, 'prospectStore'])
            ->name('api.v1.finance.prospects.store');
        Route::get('/finance/prospects/{id}', [FinanceController::class, 'prospectShow'])
            ->whereNumber('id')
            ->name('api.v1.finance.prospects.show');
        Route::patch('/finance/prospects/{id}', [FinanceController::class, 'prospectUpdate'])
            ->whereNumber('id')
            ->name('api.v1.finance.prospects.update');
        Route::get('/finance/projects', [FinanceController::class, 'projects'])
            ->name('api.v1.finance.projects');
        Route::get('/finance/projects/options', [FinanceController::class, 'projectOptions'])
            ->name('api.v1.finance.projects.options');
        Route::post('/finance/projects', [FinanceController::class, 'projectStore'])
            ->name('api.v1.finance.projects.store');
        Route::post('/finance/projects/{id}', [FinanceController::class, 'projectUpdate'])
            ->whereNumber('id')
            ->name('api.v1.finance.projects.update');
        Route::get('/finance/projects/{id}', [FinanceController::class, 'projectShow'])
            ->whereNumber('id')
            ->name('api.v1.finance.projects.show');
        Route::get('/finance/projects/{id}/contract', [FinanceController::class, 'projectContract'])
            ->whereNumber('id')
            ->name('api.v1.finance.projects.contract');
        Route::get('/finance/projects/{id}/invoice', [FinanceController::class, 'projectInvoice'])
            ->whereNumber('id')
            ->name('api.v1.finance.projects.invoice');
        Route::get('/finance/products/{id}/preview', [FinanceController::class, 'productPreview'])
            ->whereNumber('id')
            ->name('api.v1.finance.products.preview');
        Route::get('/finance/products/{id}', [FinanceController::class, 'productShow'])
            ->name('api.v1.finance.products.show');
        Route::get('/finance/vendors/{id}', [FinanceController::class, 'vendorShow'])
            ->name('api.v1.finance.vendors.show');
    });
});
