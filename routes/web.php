<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', [UserController::class, 'index'])->middleware('guest')->name('mylogin');

Route::get('/register', [UserController::class, 'signupshow']);
Route::post('/register', [UserController::class, 'signupshow'])->name('register');

Route::middleware('auth')->group(function(){
    Route::get('/dashboard', [UserController::class, 'showdashboard'])->name('dashboard');
    
    Route::get('logout', [UserController::class,'logout'])->name('logout');
});

Route::group(['prefix' => 'auth'], function() {
    Route::post('check', [UserController::class, 'login'])->name('authCheck');
    
    
});