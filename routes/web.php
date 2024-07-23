<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ResourceController;

Route::get('/', [UserController::class, 'index'])->middleware('guest')->name('login');

Route::get('/register', [UserController::class, 'signupshow']);
Route::post('/register', [UserController::class, 'signupshow'])->name('register');

Route::middleware('auth')->group(function(){
    Route::get('/dashboard', [UserController::class, 'showdashboard'])->name('dashboard');
    Route::get('logout', [UserController::class,'logout'])->name('logout');
});

Route::group(['prefix' => 'auth'], function() {
    Route::post('check', [UserController::class, 'login'])->name('authCheck');
    
    
});
Route::group(['prefix'=> 'member', 'middleware' => ['auth']], function() {
	Route::get('{type}/{action?}', [MemberController::class, 'index'])->name('member');
    Route::post('store', [MemberController::class, 'create'])->name('memberstore');
    Route::post('commission/update', [MemberController::class, 'commissionUpdate'])->name('commissionUpdate');
    Route::post('getcommission', [MemberController::class, 'getCommission'])->name('getMemberCommission');
    Route::post('getpackagecommission', [MemberController::class, 'getPackageCommission'])->name('getMemberPackageCommission');
});

Route::group(['prefix' => 'resources', 'middleware' => ['auth']], function() {
    Route::get('{type}', [ResourceController::class, 'index'])->name('resource');
    Route::post('update', [ResourceController::class, 'update'])->name('resourceupdate');
    Route::post('get/{type}/commission', [ResourceController::class, 'getCommission']);
    Route::post('get/{type}/packagecommission', [ResourceController::class, 'getPackageCommission']);
});