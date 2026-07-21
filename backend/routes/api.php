<?php

use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DeveloperApiKeyContextController;
use App\Http\Controllers\Api\V1\DeveloperApiKeyController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DeviceHeartbeatController;
use App\Http\Controllers\Api\V1\DeviceMessageController;
use App\Http\Controllers\Api\V1\DevicePairingController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\InboundMessageController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
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

    Route::get('/organizations/{organization}/devices', [DeviceController::class, 'index'])
        ->middleware('abilities:devices:read');
    Route::post('/organizations/{organization}/device-pairing-challenges', [DevicePairingController::class, 'challenge'])
        ->middleware('abilities:devices:write');
    Route::delete('/organizations/{organization}/devices/{device}', [DeviceController::class, 'revoke'])
        ->middleware('abilities:devices:write');
});

Route::prefix('v1')->middleware(['developer-api-key', 'throttle:api'])->group(function (): void {
    Route::get('/api-key', DeveloperApiKeyContextController::class)
        ->middleware('developer-ability:messages:read');
    Route::get('/messages', [MessageController::class, 'index'])
        ->middleware('developer-ability:messages:read');
    Route::post('/messages', [MessageController::class, 'store'])
        ->middleware('developer-ability:messages:write');
    Route::get('/inbound-messages', [InboundMessageController::class, 'index'])
        ->middleware('developer-ability:messages:read');
    Route::get('/contacts', [ContactController::class, 'index'])
        ->middleware('developer-ability:contacts:read');
    Route::post('/contacts', [ContactController::class, 'store'])
        ->middleware('developer-ability:contacts:write');
    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->middleware('developer-ability:campaigns:read');
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('developer-ability:campaigns:write');
    Route::get('/webhook-endpoints', [WebhookEndpointController::class, 'index'])
        ->middleware('developer-ability:webhooks:read');
    Route::post('/webhook-endpoints', [WebhookEndpointController::class, 'store'])
        ->middleware('developer-ability:webhooks:write');
    Route::delete('/webhook-endpoints/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy'])
        ->middleware('developer-ability:webhooks:write');
    Route::get('/webhook-endpoints/{webhookEndpoint}/deliveries', [WebhookEndpointController::class, 'deliveries'])
        ->middleware('developer-ability:webhooks:read');
    Route::post('/webhook-endpoints/{webhookEndpoint}/deliveries/{delivery}/replay', [WebhookEndpointController::class, 'replay'])
        ->middleware('developer-ability:webhooks:write');
});

Route::post('/v1/device/pair', [DevicePairingController::class, 'pair'])
    ->middleware('throttle:device-pairing');

Route::post('/v1/device/heartbeat', DeviceHeartbeatController::class)
    ->middleware(['device-auth', 'throttle:device-heartbeat']);

Route::prefix('v1/device')->middleware(['device-auth', 'throttle:api'])->group(function (): void {
    Route::post('/messages/lease', [DeviceMessageController::class, 'lease']);
    Route::post('/messages/{message}/status', [DeviceMessageController::class, 'update']);
    Route::post('/inbound-messages', [InboundMessageController::class, 'store']);
});
