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
use App\Http\Controllers\CashController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BillingController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('registos', [ActivityLogController::class, 'index'])->name('logs.index');
});

Route::middleware('auth')->group(function () {
    Route::get('vendas', [SaleController::class, 'index'])->middleware('can:sales.view')->name('sales.index');
Route::get('vendas/{sale}', [SaleController::class, 'show'])->middleware('can:sales.view')->name('sales.show');
Route::post('vendas/{sale}/cancelar', [SaleController::class, 'cancel'])->middleware('can:sales.cancel')->name('sales.cancel');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', fn () => view('dashboard'))->name('dashboard');

    Route::middleware('can:services.manage')->group(function () {
    Route::get('servicos', [ServiceController::class, 'index'])->name('services.index');
    Route::post('servicos', [ServiceController::class, 'store'])->name('services.store');
    Route::put('servicos/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::patch('servicos/{service}/estado', [ServiceController::class, 'toggle'])->name('services.toggle');
});

Route::middleware('can:customers.manage')->group(function () {
    Route::get('clientes', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('clientes/novo', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('clientes', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('clientes/{customer}/editar', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('clientes/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('clientes/{customer}/estado', [CustomerController::class, 'toggle'])->name('customers.toggle');
});

Route::middleware('can:services.manage')->group(function () {
    Route::get('contratos', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('contratos/novo', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('contratos', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::patch('contratos/{subscription}/estado', [SubscriptionController::class, 'status'])->name('subscriptions.status');
});

Route::get('cobrancas', [BillingController::class, 'report'])->middleware('can:billing.view')->name('billing.report');
Route::get('cobrancas/exportar', [BillingController::class, 'export'])->middleware('can:billing.view')->name('billing.export');
Route::get('cobrancas/{receipt}', [BillingController::class, 'show'])->middleware('can:billing.view')->name('billing.show');
Route::post('cobrancas/{receipt}/cancelar', [BillingController::class, 'cancel'])->middleware('can:billing.cancel')->name('billing.cancel');


Route::get('cobrar', [BillingController::class, 'collect'])->middleware('can:billing.collect')->name('billing.collect');
Route::get('devedores', [BillingController::class, 'debtors'])->middleware('can:billing.view')->name('billing.debtors');
Route::get('cobrancas/recibo/{receipt}', [BillingController::class, 'receipt'])->name('billing.receipt');
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


Route::middleware('can:cash.operate')->group(function () {
    Route::get('caixa', [CashController::class, 'current'])->name('cash.current');
    Route::post('caixa/abrir', [CashController::class, 'open'])->name('cash.open');
    Route::post('caixa/movimento', [CashController::class, 'movement'])->name('cash.movement');
    Route::get('caixa/fechar', [CashController::class, 'closeForm'])->name('cash.close.form');
    Route::post('caixa/fechar', [CashController::class, 'close'])->name('cash.close');
});

Route::middleware('can:cash.manage')->group(function () {
    Route::get('turnos', [CashController::class, 'sessions'])->name('cash.sessions');
    Route::get('caixas', [CashRegisterController::class, 'index'])->name('cash.registers.index');
    Route::post('caixas', [CashRegisterController::class, 'store'])->name('cash.registers.store');
    Route::put('caixas/{register}', [CashRegisterController::class, 'update'])->name('cash.registers.update');
    Route::patch('caixas/{register}/estado', [CashRegisterController::class, 'toggle'])->name('cash.registers.toggle');
});

Route::get('vender', [SaleController::class, 'pdv'])->middleware('can:sales.create')->name('pdv.index');
Route::get('vendas/{sale}/recibo', [SaleController::class, 'receipt'])->name('sales.receipt');
