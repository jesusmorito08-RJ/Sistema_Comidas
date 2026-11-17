<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/comidas', [StorefrontController::class, 'index'])->name('foods.index');
Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/{food}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{food}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{food}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->name('login.store');
    Route::get('/admin/ingresar', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/ingresar', [AuthController::class, 'loginAdmin'])->name('admin.login.store');
    Route::get('/crear-cuenta', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/crear-cuenta', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/salir', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/mi-cuenta', [StorefrontController::class, 'dashboard'])->name('customer.dashboard');
    Route::post('/pedido/confirmar', [CartController::class, 'checkout'])->name('checkout');
    Route::get('/mis-pedidos', [StorefrontController::class, 'orders'])->name('orders.mine');
    Route::delete('/mis-pedidos/{order}', [CartController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('/reservas', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservas', [ReservationController::class, 'store'])->name('reservations.store');
    Route::delete('/reservas/{reservation}', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/comidas', [FoodController::class, 'index'])->name('foods.index');
        Route::get('/comidas/crear', [FoodController::class, 'create'])->name('foods.create');
        Route::post('/comidas', [FoodController::class, 'store'])->name('foods.store');
        Route::get('/comidas/{food}/editar', [FoodController::class, 'edit'])->name('foods.edit');
        Route::put('/comidas/{food}', [FoodController::class, 'update'])->name('foods.update');
        Route::delete('/comidas/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
        Route::post('/comidas/{id}/restaurar', [FoodController::class, 'restore'])->name('foods.restore');
        Route::get('/pedidos', [AdminController::class, 'orders'])->name('orders');
        Route::get('/pedidos/{order}/editar', [AdminController::class, 'editOrder'])->name('orders.edit');
        Route::patch('/pedidos/{order}', [AdminController::class, 'updateOrder'])->name('orders.update');
        Route::delete('/pedidos/{order}', [AdminController::class, 'archiveOrder'])->name('orders.archive');
        Route::post('/pedidos/{id}/restaurar', [AdminController::class, 'restoreOrder'])->name('orders.restore');
        Route::get('/reservas', [AdminController::class, 'reservations'])->name('reservations');
        Route::get('/reservas/{reservation}/editar', [AdminController::class, 'editReservation'])->name('reservations.edit');
        Route::patch('/reservas/{reservation}', [AdminController::class, 'updateReservation'])->name('reservations.update');
        Route::delete('/reservas/{reservation}', [AdminController::class, 'cancelReservation'])->name('reservations.cancel');
        Route::resource('clientes', CustomerController::class)
            ->except(['show'])
            ->parameters(['clientes' => 'customer'])
            ->names([
                'index' => 'customers.index',
                'create' => 'customers.create',
                'store' => 'customers.store',
                'edit' => 'customers.edit',
                'update' => 'customers.update',
                'destroy' => 'customers.destroy',
            ]);
        Route::post('/clientes/{customer}/restaurar', [CustomerController::class, 'restore'])->name('customers.restore');
        Route::get('/reportes', [AdminController::class, 'reports'])->name('reports');
    });
});
