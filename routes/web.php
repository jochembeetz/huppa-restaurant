<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [OrderController::class, 'index'])->name('dashboard');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
