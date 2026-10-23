<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;

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
