<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('registos', [ActivityLogController::class, 'index'])->name('logs.index');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', fn () => view('dashboard'))->name('dashboard');
});

Route::middleware('can:super-admin')->group(function () {
    Route::resource('lojas', StoreController::class)
        ->parameters(['lojas' => 'store'])
        ->names('stores')
        ->except(['show', 'destroy']);
});

Route::middleware('can:users.manage')->group(function () {
    Route::resource('perfis', RoleController::class)
        ->parameters(['perfis' => 'role'])
        ->names('roles')
        ->except(['show']);

         Route::resource('utilizadores', UserController::class)
        ->parameters(['utilizadores' => 'user'])
        ->names('users')
        ->except(['show', 'destroy']);

    Route::patch('utilizadores/{user}/estado', [UserController::class, 'toggle'])->name('users.toggle');
});

Route::middleware('can:categories.manage')->group(function () {
    Route::get('categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

Route::get('produtos', [ProductController::class, 'index'])->middleware('can:products.view')->name('products.index');
Route::get('produtos/novo', [ProductController::class, 'create'])->middleware('can:products.create')->name('products.create');
Route::post('produtos', [ProductController::class, 'store'])->middleware('can:products.create')->name('products.store');
Route::get('produtos/{product}/editar', [ProductController::class, 'edit'])->middleware('can:products.update')->name('products.edit');
Route::put('produtos/{product}', [ProductController::class, 'update'])->middleware('can:products.update')->name('products.update');
Route::patch('produtos/{product}/estado', [ProductController::class, 'toggle'])->middleware('can:products.update')->name('products.toggle');

Route::get('estoque', [StockController::class, 'index'])->middleware('can:stock.view')->name('stock.index');
Route::get('estoque/{product}/historico', [StockController::class, 'history'])->middleware('can:stock.view')->name('stock.history');
Route::get('estoque/{product}/ajuste', [StockController::class, 'adjustForm'])->middleware('can:stock.adjust')->name('stock.adjust.form');
Route::post('estoque/{product}/ajuste', [StockController::class, 'adjust'])->middleware('can:stock.adjust')->name('stock.adjust');

Route::middleware('can:suppliers.manage')->group(function () {
    Route::get('fornecedores', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('fornecedores/novo', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('fornecedores', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('fornecedores/{supplier}/editar', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('fornecedores/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::patch('fornecedores/{supplier}/estado', [SupplierController::class, 'toggle'])->name('suppliers.toggle');
});

Route::get('compras', [PurchaseController::class, 'index'])->middleware('can:purchases.view')->name('purchases.index');
Route::get('compras/nova', [PurchaseController::class, 'create'])->middleware('can:purchases.create')->name('purchases.create');
Route::post('compras', [PurchaseController::class, 'store'])->middleware('can:purchases.create')->name('purchases.store');
Route::get('compras/{purchase}', [PurchaseController::class, 'show'])->middleware('can:purchases.view')->name('purchases.show');
Route::post('compras/{purchase}/receber', [PurchaseController::class, 'receive'])->middleware('can:purchases.receive')->name('purchases.receive');
Route::post('compras/{purchase}/cancelar', [PurchaseController::class, 'cancel'])->middleware('can:purchases.create')->name('purchases.cancel');
