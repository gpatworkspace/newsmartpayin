<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', [UserController::class, 'index'])->middleware('guest')->name('mylogin');
Route::get('/register', [UserController::class, 'signupshow']);
Route::get('/dashboard', [UserController::class, 'showdashboard']);

Route::group(['prefix' => 'auth'], function() {
    Route::post('check', [UserController::class, 'login'])->name('authCheck');
    Route::get('logout', [UserController::class,'logout'])->name('logout');
    
});