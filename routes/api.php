<?php

use App\Http\Controllers\Api\ItemPurchaseCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:item-purchase-code')->group(function (): void {
    Route::post('/item-purchase-codes/verify', [ItemPurchaseCodeController::class, 'verify'])
        ->name('api.item-purchase-codes.verify');
});
