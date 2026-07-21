<?php

use App\Http\Controllers\Web\BillingController;
use App\Http\Controllers\Web\PlatformAdminController;
use App\Http\Controllers\Web\PortalController;
use App\Http\Controllers\Web\WebAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [WebAuthenticationController::class, 'loginForm'])->name('login');
    Route::post('/login', [WebAuthenticationController::class, 'login'])->middleware('throttle:auth-login');
    Route::get('/register', [WebAuthenticationController::class, 'registerForm'])->name('register');
    Route::post('/register', [WebAuthenticationController::class, 'register'])->middleware('throttle:auth-register');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::post('/logout', [WebAuthenticationController::class, 'logout'])->name('logout');
    Route::get('/app', [PortalController::class, 'home'])->name('portal.home');
    Route::post('/app/organizations', [PortalController::class, 'createOrganization'])->name('portal.organizations.store');
    Route::get('/app/{organization}', [PortalController::class, 'overview'])->name('portal.overview');
    Route::get('/app/{organization}/messages', [PortalController::class, 'messages'])->name('portal.messages');
    Route::post('/app/{organization}/messages', [PortalController::class, 'send'])->name('portal.messages.send');
    Route::get('/app/{organization}/devices', [PortalController::class, 'devices'])->name('portal.devices');
    Route::post('/app/{organization}/devices/pair', [PortalController::class, 'pairingChallenge'])->name('portal.devices.pair');
    Route::delete('/app/{organization}/devices/{device}', [PortalController::class, 'revokeDevice'])->name('portal.devices.revoke');
    Route::get('/app/{organization}/developer', [PortalController::class, 'developer'])->name('portal.developer');
    Route::post('/app/{organization}/developer/api-keys', [PortalController::class, 'createApiKey'])->name('portal.api-keys.store');
    Route::delete('/app/{organization}/developer/api-keys/{apiKey}', [PortalController::class, 'revokeApiKey'])->name('portal.api-keys.revoke');
    Route::get('/app/{organization}/billing', [BillingController::class, 'show'])->name('portal.billing');
    Route::post('/app/{organization}/billing/requests', [BillingController::class, 'requestChange'])->name('portal.billing.request');
});

Route::prefix('admin')->middleware(['auth', 'verified', 'platform-admin'])->group(function (): void {
    Route::get('/', [PlatformAdminController::class, 'index'])->name('admin.index');
    Route::post('/subscription-requests/{changeRequest}/approve', [PlatformAdminController::class, 'approve'])->name('admin.requests.approve');
    Route::post('/subscription-requests/{changeRequest}/reject', [PlatformAdminController::class, 'reject'])->name('admin.requests.reject');
    Route::post('/organizations/{organization}/pause', [PlatformAdminController::class, 'pause'])->name('admin.organizations.pause');
    Route::post('/organizations/{organization}/suspend', [PlatformAdminController::class, 'suspend'])->name('admin.organizations.suspend');
});
