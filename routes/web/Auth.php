<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'login']);
    Route::get("/login", [AuthController::class, "login"])->name("login");
    Route::get("/logout", [AuthController::class, "logout"])->name('signout');
    
    // Client Forgot Password Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    // Route::get("/register", [AuthController::class, "register"])->name("register");
    // Route::get("/email_verification", [AuthController::class, "emailVerification"])->name("verification.index");
    // Route::get('/email/verify/{id}/{hash}', [AuthController::class, "emailVerificationVerify"])->middleware('signed')->name('verification.verify');
});

Route::middleware('auth')->group(function () {
    Route::get("/logout", [AuthController::class, "logout"])->name('signout');
    Route::get('/profile', [AuthController::class, "profile"])->name('profile');
});
