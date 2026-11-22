<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', StoreController::class)->name('store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::redirect('/', '/admin/productos');

        Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('usuarios', [UserController::class, 'store'])->name('users.store');
        Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::patch('productos/{product}/visibilidad', [ProductController::class, 'toggle'])->name('products.toggle');
        Route::patch('productos/{product}/posicion', [ProductController::class, 'move'])->name('products.move');
        Route::resource('productos', ProductController::class)
            ->parameters(['productos' => 'product'])
            ->except('show')
            ->names('products');
    });
});
