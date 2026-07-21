<?php

use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\DeveloperApiKeyContextController;
use App\Http\Controllers\Api\V1\DeveloperApiKeyController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', [AuthenticationController::class, 'register'])->middleware('throttle:auth-register');
    Route::post('/login', [AuthenticationController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:password-reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:password-reset');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['auth:sanctum', 'abilities:email:verify', 'throttle:6,1']);
    Route::get('/me', [AuthenticationController::class, 'me'])
        ->middleware(['auth:sanctum', 'abilities:profile:read']);
    Route::delete('/logout', [AuthenticationController::class, 'logout'])
        ->middleware('auth:sanctum');
});

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/organizations', [OrganizationController::class, 'index'])
        ->middleware('abilities:organizations:read');
    Route::post('/organizations', [OrganizationController::class, 'store'])
        ->middleware('abilities:organizations:write');

    Route::get('/organizations/{organization}/api-keys', [DeveloperApiKeyController::class, 'index'])
        ->middleware('abilities:api-keys:read');
    Route::post('/organizations/{organization}/api-keys', [DeveloperApiKeyController::class, 'store'])
        ->middleware('abilities:api-keys:write');
    Route::post('/organizations/{organization}/api-keys/{apiKey}/rotate', [DeveloperApiKeyController::class, 'rotate'])
        ->middleware('abilities:api-keys:write');
    Route::delete('/organizations/{organization}/api-keys/{apiKey}', [DeveloperApiKeyController::class, 'revoke'])
        ->middleware('abilities:api-keys:write');
});

Route::prefix('v1')->middleware(['developer-api-key', 'throttle:api'])->group(function (): void {
    Route::get('/api-key', DeveloperApiKeyContextController::class)
        ->middleware('developer-ability:messages:read');
});
