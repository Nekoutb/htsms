<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Web\AdminMfaController;
use App\Http\Controllers\Web\BillingController;
use App\Http\Controllers\Web\MarketingController;
use App\Http\Controllers\Web\PlatformAdminController;
use App\Http\Controllers\Web\PortalController;
use App\Http\Controllers\Web\WebAuthenticationController;
use App\Http\Controllers\Web\WebEmailVerificationController;
use App\Http\Controllers\Web\WebPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/language/{locale}', function (Request $request, string $locale): RedirectResponse {
    abort_unless(in_array($locale, ['en', 'fr'], true), 404);
    $request->session()->put('locale', $locale);

    return back();
})->name('locale.switch');
Route::get('/health/ready', HealthController::class)->middleware('throttle:30,1');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [WebAuthenticationController::class, 'loginForm'])->name('login');
    Route::post('/login', [WebAuthenticationController::class, 'login'])->middleware('throttle:auth-login');
    Route::get('/register', [WebAuthenticationController::class, 'registerForm'])->name('register');
    Route::post('/register', [WebAuthenticationController::class, 'register'])->middleware('throttle:auth-register');
    Route::get('/forgot-password', [WebPasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [WebPasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [WebPasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [WebPasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});

Route::get('/email/verify', [WebEmailVerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verification-notification', [WebEmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1')->name('verification.send');

Route::post('/logout', [WebAuthenticationController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'verified'])->group(function (): void {
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
    Route::post('/app/{organization}/developer/webhooks', [PortalController::class, 'createWebhook'])->name('portal.webhooks.store');
    Route::delete('/app/{organization}/developer/webhooks/{webhookEndpoint}', [PortalController::class, 'revokeWebhook'])->name('portal.webhooks.revoke');
    Route::get('/app/{organization}/marketing', [MarketingController::class, 'index'])->name('portal.marketing');
    Route::post('/app/{organization}/marketing/contacts', [MarketingController::class, 'storeContact'])->name('portal.contacts.store');
    Route::post('/app/{organization}/marketing/campaigns', [MarketingController::class, 'storeCampaign'])->name('portal.campaigns.store');
    Route::get('/app/{organization}/billing', [BillingController::class, 'show'])->name('portal.billing');
    Route::post('/app/{organization}/billing/requests', [BillingController::class, 'requestChange'])->name('portal.billing.request');
});

Route::prefix('admin/mfa')->middleware(['auth', 'verified', 'throttle:10,1'])->group(function (): void {
    Route::get('/', [AdminMfaController::class, 'show'])->name('admin.mfa.show');
    Route::post('/send', [AdminMfaController::class, 'send'])->name('admin.mfa.send');
    Route::post('/verify', [AdminMfaController::class, 'verify'])->name('admin.mfa.verify');
});

Route::prefix('admin')->middleware(['auth', 'verified', 'platform-admin'])->group(function (): void {
    Route::get('/', [PlatformAdminController::class, 'index'])->name('admin.index');
    Route::post('/users', [PlatformAdminController::class, 'storeUser'])->name('admin.users.store');
    Route::delete('/users/{user}', [PlatformAdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::post('/subscription-requests/{changeRequest}/approve', [PlatformAdminController::class, 'approve'])->name('admin.requests.approve');
    Route::post('/subscription-requests/{changeRequest}/reject', [PlatformAdminController::class, 'reject'])->name('admin.requests.reject');
    Route::post('/organizations/{organization}/pause', [PlatformAdminController::class, 'pause'])->name('admin.organizations.pause');
    Route::post('/organizations/{organization}/suspend', [PlatformAdminController::class, 'suspend'])->name('admin.organizations.suspend');
    Route::post('/organizations/{organization}/channels/{channel}', [PlatformAdminController::class, 'toggleChannel'])
        ->whereIn('channel', ['inbound', 'outbound'])->name('admin.organizations.channels');
});
