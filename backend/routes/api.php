<?php

use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/organizations', [OrganizationController::class, 'index'])
        ->middleware('abilities:organizations:read');
    Route::post('/organizations', [OrganizationController::class, 'store'])
        ->middleware('abilities:organizations:write');
});
