<?php

use App\Http\Controllers\Api\GetSingleOrderController;
use App\Http\Middleware\ApiKeyGuard;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiKeyGuard::class)->group(function () {
    Route::get('/orders/{order}', GetSingleOrderController::class);
});