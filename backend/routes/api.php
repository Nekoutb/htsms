<?php

use App\Http\Controllers\Api\V1\DeveloperApiKeyContextController;
use App\Http\Controllers\Api\V1\DeveloperApiKeyController;
use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

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
